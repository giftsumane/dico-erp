<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = $this->query($request)
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $actions = AuditLog::query()
            ->where('company_id', app('activeCompany')->id)
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('audit-logs.index', compact('logs', 'actions'));
    }

    public function export(Request $request): StreamedResponse
    {
        $logs = $this->query($request)->oldest('id')->cursor();
        $filename = 'auditoria-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Data', 'Acção', 'Utilizador', 'Loja',
                'Entidade', 'ID', 'Detalhes', 'IP',
            ], ';');

            foreach ($logs as $log) {
                fputcsv($output, [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->action,
                    $log->user?->name,
                    $log->store?->name,
                    class_basename($log->subject_type),
                    $log->subject_id,
                    json_encode($log->metadata, JSON_UNESCAPED_UNICODE),
                    $log->ip_address,
                ], ';');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function query(Request $request): Builder
    {
        $company = app('activeCompany');
        $store = app('activeStore');
        $data = $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        return AuditLog::query()
            ->with(['user:id,name', 'store:id,name'])
            ->where('company_id', $company->id)
            ->when($store, fn (Builder $query) =>
                $query->where(fn (Builder $scope) => $scope
                    ->where('store_id', $store->id)
                    ->orWhereNull('store_id'))
            )
            ->when($data['action'] ?? null, fn (Builder $query, string $action) =>
                $query->where('action', $action)
            )
            ->when($data['date_from'] ?? null, fn (Builder $query, string $date) =>
                $query->whereDate('created_at', '>=', $date)
            )
            ->when($data['date_to'] ?? null, fn (Builder $query, string $date) =>
                $query->whereDate('created_at', '<=', $date)
            )
            ->when($data['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $scope) use ($search) {
                    $scope->where('action', 'like', "%{$search}%")
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhere('subject_id', $search)
                        ->orWhereHas('user', fn (Builder $user) =>
                            $user->where('name', 'like', "%{$search}%")
                        );
                });
            });
    }
}
