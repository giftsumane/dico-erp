# Diagnóstico e proposta — Gestão de Operações de Bares (CSV Agência)

Data da análise: 29 de Outubro de 2026  
Branch de trabalho: `feature/gestao-bares-csv`  
Base analisada: `main` no commit `1ae5829`

## 1. Resumo executivo

O DICO já contém a maior parte dos componentes transaccionais necessários: isolamento por empresa e loja, associação de utilizadores, catálogo, stock por loja, movimentos auditáveis, lotes, compras, POS, pagamentos, sessões de caixa, devoluções, relatórios e feature flags.

A adaptação não deve substituir estes módulos. A solução mais simples é representar cada ponto que detém stock e responsabilidade financeira — armazém central e cada balcão/bar — como uma `store` da empresa CSV, e adicionar uma camada de evento que agrupe essas lojas e defina equipas, funções, activos e período operacional.

As lacunas principais são o workflow de transferências com confirmação, segregação de funções, contagens físicas e reconciliações, idempotência explícita, auditoria de decisões sensíveis e testes automatizados. Antes de operar num festival, estas lacunas são bloqueantes.

## 2. Estado do repositório e ambiente

- O directório inicial não continha um repositório. O clone SSH falhou por ausência de chave pública no ambiente; o acesso foi confirmado através da ligação GitHub e o repositório foi clonado por HTTPS.
- Remoto: `origin = https://github.com/giftsumane/dico-erp.git`.
- A branch remota `feature/gestao-bares-csv` não existia no momento da análise.
- Foi criada localmente a branch `feature/gestao-bares-csv`, a partir de `origin/main` (`1ae5829`).
- A árvore estava limpa antes deste documento.
- O projecto exige PHP `^8.3` e fixa a plataforma Composer em PHP `8.3.36`. O PHP local disponível é 8.4.4; staging usa PHP CloudLinux 8.3.30. A implementação deve ser validada também em PHP 8.3.
- Não existem dependências instaladas localmente (`vendor/` e `node_modules/` ausentes), por isso esta entrega não executa a aplicação.
- A suíte actual contém apenas os testes de exemplo do Laravel; não há cobertura funcional dos módulos de negócio.

## 3. Arquitectura existente

### 3.1 Contexto multi-empresa e multi-loja

`EnsureBusinessContext` selecciona uma empresa activa associada ao utilizador, limita as lojas às associações activas desse utilizador e expõe `activeCompany`, `activeStore` e `activeRole`. Os controladores, em geral, filtram consultas por esses IDs.

Estruturas reutilizáveis:

- `companies`: entidade empresarial e configuração funcional;
- `stores`: local operacional pertencente a uma empresa;
- `company_user`: associação e perfil global na empresa;
- `store_user`: acesso do utilizador à loja;
- `company_features`: activação de módulos por empresa.

Limitação: o contexto depende de sessão e a protecção de registos é repetida manualmente nos controladores. Não existem global scopes, policies ou Form Requests que garantam uniformemente o isolamento. Cada novo endpoint deve filtrar e autorizar no backend.

### 3.2 Perfis e autorização

O sistema possui quatro perfis fixos no enum de `company_user`: `owner`, `admin`, `manager` e `cashier`. `EnsureRole` autoriza grupos de rotas. O controlo funciona no backend, mas é demasiado amplo para a operação dos bares.

Lacunas:

- não existem os perfis de armazém, entrega, atendente e supervisor;
- o perfil é global à empresa, não específico por evento ou balcão;
- não existem permissões por capacidade nem policies por recurso;
- não há regra de “quatro olhos”: o mesmo utilizador pode iniciar e concluir operações sensíveis permitidas ao seu perfil.

Recomendação: manter os quatro perfis comerciais para compatibilidade e criar funções operacionais específicas do evento em `event_assignments`, ligadas a capacidades/policies. Não ampliar o enum `company_user.role` com todas as funções do festival.

### 3.3 Produtos e stock

`products`, `stocks`, `stock_movements` e `stock_batches` já suportam:

- saldo por produto e loja, com unicidade por `store_id + product_id`;
- produtos simples, variantes e serviços;
- stock simples ou por lote;
- entradas, saídas, ajustes, compras, vendas, devoluções e tipos reservados para transferências;
- utilizador, saldo anterior/posterior, referência e instante do movimento;
- locks pessimistas (`lockForUpdate`) e transacções no `StockService`;
- rejeição de stock negativo nas saídas normais.

O enum de movimentos já inclui `transfer_in` e `transfer_out`, mas não existe entidade, controller ou fluxo de transferência. Um ajuste manual é aplicado imediatamente pelo próprio gestor, sem aprovação. Também não existe categoria própria para perda/quebra, apesar de poder ser registada como ajuste com nota.

### 3.4 POS, vendas e pagamentos

O POS actual:

- exige caixa aberto para o utilizador e loja activos;
- cria venda, linhas e pagamento dentro de transacção;
- suporta `cash`, `mpesa`, `emola`, `card`, `bank_transfer` e `other`;
- suporta venda total, parcial e a crédito;
- associa venda, pagamento e caixa ao utilizador responsável;
- valida o catálogo da empresa e disponibilidade do stock;
- usa o `StockService` para dedução e alocação por lote;
- regista venda como concluída e produz recibo.

Pontos a reforçar:

- não há chave idempotente; um POST repetido pode criar nova venda;
- não existe endpoint de cancelamento/anulação, apesar de `sales.status` aceitar `cancelled`;
- descontos podem ser aplicados por qualquer perfil que aceda ao POS, sem limite ou aprovação;
- o modelo é uma venda por um caixa; não existe separação atendente/pedido/caixa;
- a verificação inicial de stock é seguida por remoção com lock, o que protege o saldo, mas a resposta de concorrência precisa de testes;
- pagamentos electrónicos têm referência opcional e não possuem estado de conciliação/comprovativo.

Para a primeira versão do festival recomenda-se manter o atendimento e cobrança no mesmo terminal de caixa. Separar pedido do atendente e cobrança deve ser uma fase posterior, se o fluxo real confirmar essa necessidade; fazê-lo já aumentaria passos e latência.

### 3.5 Caixa

O caixa suporta abertura, movimentos manuais e fecho. Calcula:

`esperado = fundo inicial + vendas em numerário + entradas manuais - saídas manuais`

e guarda o valor contado e a diferença. Existe histórico por loja.

Lacunas:

- uma sessão é identificada por loja + utilizador, sem evento/turno/balcão explícito;
- não há aprovação de diferenças nem motivo obrigatório quando a diferença é relevante;
- o fecho não reconcilia M-Pesa, e-Mola, cartão e outros métodos individualmente;
- movimentos manuais não têm aprovação;
- é necessário confirmar e testar o comportamento sob duas tentativas simultâneas de abertura/fecho; não há índice único que impeça duas sessões abertas.

### 3.6 Compras, devoluções e relatórios

Compras confirmadas alimentam stock e preservam referência. Devoluções de venda são transaccionais, bloqueiam a venda, controlam quantidades previamente devolvidas, restauram lotes e podem registar saída de caixa.

Dashboard e relatórios já calculam vendas, ticket médio, produtos, pagamentos por método e contas a receber, sempre para a loja activa. Devem ser reutilizados e receber uma dimensão de evento e consolidação multi-loja.

### 3.7 Qualidade e riscos técnicos observados

- Não há testes de negócio, factories completas ou seed de cenário operacional.
- O `README.md` é o texto padrão do Laravel e não documenta instalação ou domínio.
- A autorização e o isolamento dependem de disciplina em cada controller.
- Há regras de negócio extensas em controllers, especialmente POS e relatórios; novos workflows devem usar services/actions para serem testáveis.
- Não existe trilho de auditoria genérico para mudanças de estado, aprovações ou tentativas rejeitadas.
- Não há API/offline mode. A operação depende de conectividade com o servidor; isto deve entrar no plano operacional do evento.

## 4. Modelo operacional recomendado

### 4.1 Mapeamento para estruturas existentes

- Uma `company`: CSV Agência (ou empresa operacional definida pela gestão).
- Uma `store` para o armazém central do evento.
- Uma `store` por balcão que possua stock e caixa próprios (quatro no cenário inicial).
- `store_user` continua a determinar onde cada utilizador pode operar.
- `stocks` continua a ser a fonte do saldo físico por ponto.
- `cash_registers`, `sales` e `payments` continuam ligados à loja/balcão.

Este mapeamento evita um segundo motor de stock. Se, após validação, um “bar” tiver vários balcões com stock comum, o bar será a `store` e será adicionada uma entidade leve `service_points` apenas para identificar terminais/caixas. Não se deve decidir isso sem fechar o desenho físico.

### 4.2 Novas entidades propostas

#### Núcleo do evento

`events`

- `company_id`, código, nome, local, início/fim, timezone, estado;
- estados: draft, prepared, active, closing, closed, cancelled.

`event_stores`

- `event_id`, `store_id`, tipo (`central_warehouse`, `bar`), nome operacional, sequência, estado;
- único por evento + loja.

`event_assignments`

- `event_id`, `store_id` opcional, `user_id`, função operacional, início/fim e estado;
- funções iniciais: event_manager, warehouse_keeper, delivery_operator, cashier, attendant, bar_supervisor;
- índices por evento, loja, utilizador e estado.

`event_assets`

- `event_id`, `store_id`, tipo, código/etiqueta, descrição, responsável, estado de entrega e devolução;
- permite configurar congelador, balde e geleira sem valores fixos no código.

#### Transferências e reposições

`stock_transfers`

- empresa, evento, origem, destino, número e chave idempotente;
- estado: requested, approved, preparing, dispatched, partially_received, received, rejected, cancelled;
- `requested_by`, `approved_by`, `prepared_by`, `dispatched_by`, `received_by`;
- timestamps próprios, motivo, notas e versão para concorrência;
- restrições: origem diferente do destino; ambas pertencem à empresa/evento; criador não confirma a própria operação sensível.

`stock_transfer_items`

- transferência, produto/lote, quantidade pedida, aprovada, expedida, recebida, recusada e motivo da diferença;
- único por transferência + produto + lote.

Regra contabilística: ao despachar, bloquear e remover da origem (`transfer_out`); ao receber, adicionar ao destino (`transfer_in`). O cabeçalho conserva quantidades em trânsito e diferenças. Cada transição é atómica e idempotente.

#### Contagens e reconciliação

`stock_counts` e `stock_count_items`

- evento, loja, turno/instante, estado, contador, revisor e aprovador;
- saldo esperado congelado, quantidade física, diferença, custo estimado, motivo e decisão;
- a aprovação cria ajuste, nunca a simples digitação da contagem.

`cash_reconciliations` e `cash_reconciliation_lines`

- ligadas ao caixa e evento;
- uma linha por método, valor esperado, declarado, confirmado, diferença, referência/comprovativo e estado;
- numerário usa contagem física; electrónicos usam totais/IDs do provedor.

`operation_approvals`

- recurso polimórfico, tipo de decisão, solicitante, decisor, estado, motivo e timestamps;
- usada para ajustes, perdas, cancelamentos, descontos excepcionais e diferenças de fecho;
- validação obrigatória de decisor diferente do solicitante.

`audit_events`

- empresa/evento/loja, utilizador, acção, recurso, antes/depois em JSON, IP/user-agent e instante;
- append-only na aplicação; regista também rejeições relevantes.

## 5. Fluxos propostos

### 5.1 Preparação do evento

1. Administrador cria o evento e associa o armazém e os balcões (`stores`).
2. Gestor atribui utilizadores e funções por evento/local.
3. Regista activos físicos por balcão.
4. Armazém recebe mercadoria por compra/entrada existente.
5. Gestor aprova transferências de stock inicial; entrega e recepção são confirmadas por pessoas diferentes.
6. Supervisor faz contagem inicial do balcão e resolve diferenças antes de abrir vendas.

### 5.2 Reposição rápida

1. Supervisor do bar cria pedido (produtos favoritos e quantidades predefinidas na UI).
2. Gestor ou responsável autorizado aprova, salvo limites previamente autorizados.
3. Armazém prepara e despacha; o sistema retira da origem e gera código/QR curto.
4. Entregador transporta sem poder confirmar a recepção.
5. Supervisor/recebedor confirma quantidades no balcão; diferenças exigem motivo.

Para reduzir filas, pedido, aprovação e preparação podem ocorrer num único ecrã de “fila de reposições”, mas cada acção preserva autor e timestamp.

### 5.3 Venda e caixa

1. Caixa abre sessão para o balcão com fundo inicial.
2. POS usa catálogo curto, pesquisa e botões de produtos frequentes.
3. A venda concluída reduz stock do balcão e separa pagamento por método.
4. Repetição do submit com a mesma chave retorna o resultado original.
5. Cancelamento ou desconto acima do limite cria pedido de aprovação; não altera directamente a venda.
6. Devolução reutiliza o módulo existente, com autorização reforçada.

### 5.4 Fecho

1. Encerrar novas operações do turno/local.
2. Fazer contagem física cega (sem mostrar primeiro o esperado, quando operacionalmente viável).
3. Calcular esperado: inicial + transferências recebidas + devoluções de cliente - vendas - transferências expedidas - devoluções ao armazém - perdas/ajustes aprovados.
4. Contar numerário e reconciliar cada método electrónico separadamente.
5. Exigir motivo e aprovação para diferenças acima das tolerâncias configuradas.
6. Fechar evento apenas quando transferências em trânsito, caixas e contagens estiverem resolvidos.

## 6. Matriz de segregação inicial

| Operação | Cria | Aprova | Executa/entrega | Confirma |
|---|---|---|---|---|
| Transferência inicial/reposição | supervisor/warehouse | gestor | armazém/entregador | receptor do bar |
| Ajuste/perda | operador/supervisor | gestor diferente | sistema após aprovação | auditoria |
| Cancelamento | caixa/supervisor | gestor diferente | sistema | auditoria |
| Fecho de caixa | caixa | supervisor/gestor | caixa | supervisor |
| Contagem de stock | contador | supervisor/gestor | sistema após aprovação | revisor diferente |

As combinações exactas devem ser configuráveis por capacidade. O backend deve impedir que o mesmo utilizador ocupe duas etapas incompatíveis na mesma operação, mesmo que possua ambas as funções.

## 7. Plano de implementação por fases

### Fase 0 — Fundação e testes de regressão

- documentar instalação e preparar `.env.testing` isolado;
- criar factories/seeders para duas empresas, armazém, quatro balcões, operadores, produtos e métodos;
- cobrir os fluxos existentes de contexto, stock, POS, pagamentos, caixa e devoluções;
- extrair regras novas para services/actions e introduzir policies.

Critério: baseline verde em PHP 8.3 e nenhuma consulta cruzada entre empresas/lojas.

### Fase 1 — Evento, locais e equipas

- migrations/modelos de evento, associação às lojas, funções e activos;
- selector de evento e middleware de contexto;
- policies/capacidades e UI de configuração;
- feature flag `event_bars`, sem alterar comportamento das empresas comerciais.

### Fase 2 — Transferências e reposições

- workflow completo, services transaccionais, locks e idempotência;
- fila operacional rápida, comprovativo/código de entrega e histórico;
- suporte a lotes e recepção parcial;
- perdas/devoluções ao armazém como movimentos explícitos e aprovados.

### Fase 3 — POS e caixa do evento

- ligar venda/caixa ao evento e reforçar idempotência;
- permissões para desconto, devolução e cancelamento;
- reconciliação por método e comprovativos electrónicos;
- limites configuráveis e aprovação de diferenças.

### Fase 4 — Contagem e reconciliação de stock

- contagens de abertura, turno e fecho;
- snapshot do esperado, revisão e ajustes só após aprovação;
- bloqueios de encerramento e relatório de diferenças.

### Fase 5 — Dashboard e piloto

- consolidação por evento, balcão, operador, método e produto;
- reposições, perdas, diferenças, operações pendentes e trilho de auditoria;
- teste de carga, simulação de quatro balcões e ensaio operacional;
- staging separado com base de dados independente; nunca trocar automaticamente o staging actual de `main`.

## 8. Testes necessários

### Isolamento e autorização

- utilizador de uma empresa/evento/loja não lê nem altera dados de outra;
- cada função permite apenas capacidades previstas;
- autor não aprova a própria operação sensível;
- acesso directo por URL/ID é rejeitado, não apenas escondido na UI.

### Stock e transferências

- entrada, saída, ajuste aprovado, perda, transferência e devolução;
- despacho reduz origem uma vez; recepção aumenta destino uma vez;
- recepção parcial e divergente; lotes preservados;
- stock insuficiente e negativo rejeitados;
- dois operadores concorrentes não gastam o mesmo saldo;
- reenvio com mesma idempotency key não duplica movimento.

### POS, pagamentos e devoluções

- venda deduz stock e liga operador/caixa/evento/balcão;
- todos os métodos, pagamento parcial e referências;
- POST repetido não duplica venda/pagamento;
- venda concluída é imutável fora dos workflows autorizados;
- cancelamento/devolução restaura stock e caixa correctamente, inclusive lotes;
- descontos acima de limite aguardam aprovação.

### Caixa e reconciliação

- abertura concorrente não cria dois caixas para a mesma responsabilidade;
- esperado de numerário inclui apenas movimentos aplicáveis;
- electrónicos são conciliados separadamente;
- diferença exige motivo/aprovação; fecho repetido é idempotente.

### Contagens, relatórios e auditoria

- fórmula de stock não duplica transferências/vendas/devoluções;
- snapshot não muda retroactivamente;
- totais por evento equivalem à soma dos balcões;
- auditoria contém actor, transição, antes/depois e instante;
- relatórios permanecem isolados e consistentes após operações concorrentes.

### Não funcionais

- carga simultânea de quatro balcões e fila de reposição;
- índices/queries sem N+1 nas telas operacionais;
- comportamento perante perda de ligação e duplo clique;
- build frontend e testes em PHP 8.3/MySQL ou MariaDB compatível com staging.

## 9. Ficheiros previstos

Lista indicativa; os nomes finais devem ser confirmados em cada fase.

### A alterar

- `app/Support/CompanyFeatures.php`
- `app/Support/BusinessProfiles.php`
- `bootstrap/app.php`
- `routes/web.php`
- `app/Http/Middleware/EnsureBusinessContext.php` (ou novo middleware de evento, evitando sobrecarregar este)
- `app/Models/Company.php`, `Store.php`, `User.php`, `Sale.php`, `CashRegister.php`, `StockMovement.php`
- `app/Services/StockService.php`
- `app/Http/Controllers/PosController.php`, `CashRegisterController.php`, `DashboardController.php`, `ReportController.php`, `SaleReturnController.php`
- `resources/views/layouts/app.blade.php`, POS, caixa, dashboard e relatórios
- `database/seeders/DatabaseSeeder.php` (com seeders específicos)
- `README.md`, `phpunit.xml`

### A criar

- migrations aditivas para eventos, associações, funções, activos, transferências, contagens, reconciliações, aprovações, auditoria e chaves/índices de idempotência;
- models correspondentes (`Event`, `EventStore`, `EventAssignment`, `EventAsset`, `StockTransfer`, `StockTransferItem`, `StockCount`, `StockCountItem`, `CashReconciliation`, `CashReconciliationLine`, `OperationApproval`, `AuditEvent`);
- policies e middleware/contexto do evento;
- Form Requests para transições críticas;
- services/actions como `TransferStock`, `ReceiveStockTransfer`, `ApproveOperation`, `CompleteEventSale`, `CloseEventCashRegister` e `ReconcileStockCount`;
- controllers e views de configuração, transferências, contagens e reconciliação;
- factories, seeders do cenário CSV e testes Feature/Unit/Concurrency por domínio.

Não se deve modificar migrations já aplicadas; todas as mudanças estruturais serão migrations novas e reversíveis.

## 10. Decisões que precisam de validação antes da Fase 1

1. Cada um dos quatro balcões mantém stock próprio? Se sim, cada balcão será uma `store`, como recomendado.
2. “Bar” e “balcão” são sinónimos no primeiro evento, ou um bar pode possuir vários balcões com stock partilhado?
3. O atendente cria um pedido separado para o caixa, ou o caixa regista a venda completa? Recomenda-se venda completa no caixa para o MVP.
4. Quem pode aprovar reposições e quais limites dispensam aprovação manual?
5. Quais tolerâncias monetárias e de stock exigem aprovação/escalonamento?
6. Há Internet/rede local estável no recinto? Sem isso, deve ser planeado um modo de continuidade operacional antes do festival.
7. Os comprovativos M-Pesa/e-Mola serão digitados, fotografados ou integrados por API?
8. Qual é a definição de turno e pode um operador trocar de balcão durante o turno?

## 11. Recomendação de deploy

Criar um staging separado para `feature/gestao-bares-csv`, com base de dados independente e dados fictícios do festival. Só integrar em `main` por pull request após testes de regressão e piloto. O directório e a base de dados do staging actual não devem ser reutilizados para testes destrutivos nem ter a branch trocada automaticamente.
