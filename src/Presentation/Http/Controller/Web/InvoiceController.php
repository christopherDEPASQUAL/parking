<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Web;

use App\Application\DTO\Invoices\GetInvoiceRequest;
use App\Application\UseCase\Invoices\GetInvoiceData;

final class InvoiceController
{
    public function __construct(private readonly GetInvoiceData $getInvoiceData) {}

    public function show(): void
    {
        try {
            $request = GetInvoiceRequest::fromArray($_GET);
            $data = $this->getInvoiceData->execute($request);
            $this->renderInvoice($data);
        } catch (\Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderInvoice(array $data): void
    {
        header('Content-Type: text/html; charset=utf-8');

        $currency = $data['currency'] ?? 'EUR';
        $itemsHtml = '';
        foreach ($data['items'] as $item) {
            $label = htmlspecialchars((string) $item['label'], ENT_QUOTES);
            $qty = (int) ($item['quantity'] ?? 1);
            $unit = $this->formatMoney((int) ($item['unit_price_cents'] ?? 0), $currency);
            $total = $this->formatMoney((int) ($item['total_cents'] ?? 0), $currency);
            $itemsHtml .= '<tr>'
                . '<td>' . $label . '</td>'
                . '<td class="right">' . $qty . '</td>'
                . '<td class="right">' . $unit . '</td>'
                . '<td class="right">' . $total . '</td>'
                . '</tr>';
        }

        $customer = $data['customer'] ?? [];
        $parking = $data['parking'] ?? [];
        $context = $data['context'] ?? [];

        $metaRows = [
            ['Invoice', $data['invoice_id'] ?? '-'],
            ['Issued', $data['issued_at'] ?? '-'],
            ['Type', $data['type'] ?? '-'],
            ['Status', $data['status'] ?? '-'],
        ];

        $contextRows = [];
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES);
            }
            $contextRows[] = [ucwords(str_replace('_', ' ', (string) $key)), (string) $value];
        }

        echo '<!DOCTYPE html><html><head><title>Invoice ' . htmlspecialchars((string) ($data['invoice_id'] ?? ''), ENT_QUOTES) . '</title>';
        echo '<style>
            :root { --paper:#ffffff; --ink:#1f1d1a; --muted:#6f665c; --accent:#b04a2a; --bg:#f3efe6; }
            * { box-sizing: border-box; }
            body { margin:0; font-family:"Courier New", Courier, monospace; background: radial-gradient(circle at top, #fff7e6, var(--bg)); color:var(--ink); }
            .wrap { max-width: 820px; margin: 28px auto; padding: 0 16px; }
            .ticket { background:var(--paper); border:1px solid #e0d6c7; box-shadow: 0 12px 28px rgba(0,0,0,0.08); padding: 24px; }
            .header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; border-bottom:2px dashed #d9c9b4; padding-bottom:16px; }
            .title { letter-spacing: 3px; font-size: 20px; text-transform: uppercase; margin:0; }
            .meta { font-size: 12px; color:var(--muted); margin-top:6px; }
            .section { margin-top: 18px; }
            .section h2 { font-size: 12px; text-transform: uppercase; letter-spacing: 2px; margin: 0 0 8px 0; color: var(--accent); }
            table { width:100%; border-collapse: collapse; font-size: 13px; }
            th { text-align:left; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); border-bottom:1px dashed #d9c9b4; padding:6px 0; }
            td { padding:6px 0; border-bottom:1px dashed #efe5d6; }
            .right { text-align:right; }
            .total-row td { border-top:2px solid #1f1d1a; border-bottom:none; padding-top:10px; font-weight:bold; font-size: 14px; }
            .grid { display:grid; grid-template-columns: 1fr 1fr; gap: 16px; }
            .box { border:1px dashed #d9c9b4; padding: 12px; background:#fff9f1; }
            .box .label { color:var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
            .box .value { margin-top:4px; font-size: 13px; }
            .print { margin-top:16px; text-align:center; }
            .print button { background:var(--accent); color:#fff; border:none; padding:10px 18px; font-size:12px; letter-spacing:1px; text-transform:uppercase; cursor:pointer; }
            .footer { text-align:center; font-size:11px; color:var(--muted); margin-top:16px; }
            @media print {
                body { background:#fff; }
                .print { display:none; }
                .ticket { box-shadow:none; border:none; }
            }
        </style></head><body><div class="wrap"><div class="ticket">';

        echo '<div class="header">';
        echo '<div><h1 class="title">Parking Invoice</h1><div class="meta">Ref ' . htmlspecialchars((string) ($data['invoice_id'] ?? ''), ENT_QUOTES) . '</div></div>';
        echo '<div class="meta">';
        foreach ($metaRows as [$label, $value]) {
            echo '<div><strong>' . htmlspecialchars($label, ENT_QUOTES) . ':</strong> ' . htmlspecialchars((string) $value, ENT_QUOTES) . '</div>';
        }
        echo '</div></div>';

        echo '<div class="section grid">';
        echo '<div class="box"><div class="label">Customer</div><div class="value">'
            . htmlspecialchars((string) ($customer['name'] ?? '-'), ENT_QUOTES)
            . '<br>' . htmlspecialchars((string) ($customer['email'] ?? '-'), ENT_QUOTES)
            . '</div></div>';
        echo '<div class="box"><div class="label">Parking</div><div class="value">'
            . htmlspecialchars((string) ($parking['name'] ?? '-'), ENT_QUOTES)
            . '<br>' . htmlspecialchars((string) ($parking['address'] ?? '-'), ENT_QUOTES)
            . '</div></div>';
        echo '</div>';

        if ($contextRows !== []) {
            echo '<div class="section"><h2>Details</h2><table>';
            foreach ($contextRows as [$label, $value]) {
                echo '<tr><td>' . htmlspecialchars($label, ENT_QUOTES) . '</td><td class="right">' . htmlspecialchars($value, ENT_QUOTES) . '</td></tr>';
            }
            echo '</table></div>';
        }

        echo '<div class="section"><h2>Line Items</h2><table>';
        echo '<thead><tr><th>Item</th><th class="right">Qty</th><th class="right">Unit</th><th class="right">Total</th></tr></thead>';
        echo '<tbody>' . $itemsHtml . '</tbody>';
        echo '<tfoot><tr class="total-row"><td colspan="3" class="right">Total</td><td class="right">' . $this->formatMoney((int) ($data['total_cents'] ?? 0), $currency) . '</td></tr></tfoot>';
        echo '</table></div>';

        echo '<div class="print"><button onclick="window.print()">Print</button></div>';
        echo '<div class="footer">Thank you for your trust. Keep this receipt for your records.</div>';
        echo '</div></div></body></html>';
    }

    private function renderError(string $message): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><title>Invoice Error</title></head><body>';
        echo '<h1>Invoice Error</h1>';
        echo '<p>' . htmlspecialchars($message, ENT_QUOTES) . '</p>';
        echo '</body></html>';
    }

    private function formatMoney(int $cents, string $currency): string
    {
        $value = number_format($cents / 100, 2, '.', ' ');
        return $value . ' ' . $currency;
    }
}
