<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class QrController extends Controller
{
    public function show(Request $request, string $url)
    {
        // URL is passed encoded in the route param
        $decoded = urldecode($url);

        // ✅ Endroid v6 compatible simple usage (no setEncoding / no setErrorCorrectionLevel)
        $qrCode = new QrCode($decoded);

        // These usually exist and are safe; if you get errors, remove them too.
        if (method_exists($qrCode, 'setSize')) {
            $qrCode->setSize(420);
        }
        if (method_exists($qrCode, 'setMargin')) {
            $qrCode->setMargin(8);
        }

        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        // filename from query (optional)
        $filename = $request->query('filename', 'qr-offre');
        $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '-', (string) $filename);
        if (!str_ends_with($filename, '.svg')) {
            $filename .= '.svg';
        }

        $headers = [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
        ];

        // download mode
        if ($request->boolean('download')) {
            $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        }

        return response($result->getString(), 200, $headers);
    }
}
