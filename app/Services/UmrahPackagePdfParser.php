<?php

namespace App\Services;

use Carbon\Carbon;
use Smalot\PdfParser\Parser;

class UmrahPackagePdfParser
{
    public function parseFile(string $pdfPath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);
        $text = $this->normalizeText($pdf->getText());

        return $this->parseText($text);
    }

    public function parseText(string $text): array
    {
        $hotels = $this->extractHotels($text);
        $itineraries = $this->extractFlights($text);
        $passengersNames = $this->extractPassengers($text);

        return [
            'hotels' => $hotels,
            'itineraries' => $itineraries,
            'passenger_names' => $passengersNames,
            'raw_text' => $text,
        ];
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/[ \t]+/", " ", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        return trim($text);
    }

    private function extractSection(string $text, string $startHeader, array $endHeaders): ?string
    {
        $pos = stripos($text, $startHeader);
        if ($pos === false) {
            return null;
        }

        $slice = substr($text, $pos + strlen($startHeader));
        $endPos = null;

        foreach ($endHeaders as $end) {
            $p = stripos($slice, $end);
            if ($p !== false) {
                $endPos = $endPos === null ? $p : min($endPos, $p);
            }
        }

        $section = $endPos === null ? $slice : substr($slice, 0, $endPos);
        return trim($section);
    }

    private function extractHotels(string $text): array
    {
        $section = $this->extractSection($text, 'HOTEL DETAILS:', ['PRICINGS', 'IMPORTANT NOTES:', 'FLIGHT DETAILS']);
        if (!$section) {
            return [];
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", $section)), fn ($l) => $l !== ''));
        $hotels = [];

        $currentCity = null;
        $hotelName = null;
        $dateIn = null;
        $dateOut = null;
        $nights = null;
        $roomType = null;
        $mealType = null;

        $flush = function () use (&$hotels, &$currentCity, &$hotelName, &$dateIn, &$dateOut, &$nights, &$roomType, &$mealType) {
            if (!$currentCity || !$hotelName) {
                $currentCity = null;
                $hotelName = null;
                $dateIn = null;
                $dateOut = null;
                $nights = null;
                $roomType = null;
                $mealType = null;
                return;
            }

            $hotels[] = array_filter([
                'city' => $currentCity,
                'hotel_name' => $hotelName,
                'date_in' => $dateIn,
                'date_out' => $dateOut,
                'nights' => $nights,
                'type' => $roomType,
                'meals' => $mealType,
            ], fn ($v) => $v !== null && $v !== '');

            $currentCity = null;
            $hotelName = null;
            $dateIn = null;
            $dateOut = null;
            $nights = null;
            $roomType = null;
            $mealType = null;
        };

        foreach ($lines as $line) {
            if (preg_match('/^(Makkah|Madinah)\s*Hotel/i', $line, $m)) {
                $flush();
                $currentCity = ucfirst(strtolower($m[1]));

                $parts = preg_split('/[-–—]/', $line, 2);
                if (isset($parts[1])) {
                    $hotelName = trim($parts[1]);
                }
                continue;
            }

            if (preg_match('/^Check\s*in\s*date\s*:\s*(.+)$/i', $line, $m)) {
                $dateIn = $this->parseDate($m[1]);
                continue;
            }
            if (preg_match('/^Check\s*out\s*date\s*:\s*(.+)$/i', $line, $m)) {
                $dateOut = $this->parseDate($m[1]);
                continue;
            }
            if (preg_match('/^Duration\s*of\s*stay\s*:\s*(\d+)/i', $line, $m)) {
                $nights = (int) $m[1];
                continue;
            }
            if (preg_match('/^Room\s*Type\s*:\s*(.+)$/i', $line, $m)) {
                $roomType = trim($m[1]);
                continue;
            }
            if (preg_match('/^Meal\s*Type\s*:\s*(.+)$/i', $line, $m)) {
                $mealType = trim($m[1]);
                continue;
            }

            if ($hotelName === null && $currentCity !== null && str_contains(strtolower($line), 'hotel')) {
                $hotelName = trim(preg_replace('/^\s*(Makkah|Madinah)\s*Hotel\s*[-–—]?\s*/i', '', $line) ?? $line);
            }
        }

        $flush();
        return $hotels;
    }

    private function extractFlights(string $text): array
    {
        $section = $this->extractSection($text, 'FLIGHT DETAILS', ['HOTEL DETAILS:', 'PRICINGS', 'IMPORTANT NOTES:']);
        if (!$section) {
            return [];
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", $section)), fn ($l) => $l !== ''));

        $itins = [];
        $sr = 1;

        foreach ($lines as $line) {
            // Very best-effort parsing. Many supplier PDFs just contain header text without actual flight segments.
            // Try patterns like: "EK 002 LHR JED 11-Feb-2027 10:30 18:20"
            if (preg_match('/^([A-Z0-9]{2,3})\s*[-]?\s*([0-9]{1,4})\s+([A-Z]{3})\s+([A-Z]{3})\s+(.+)$/', $line, $m)) {
                $rest = trim($m[5]);
                $depDate = null;
                $depTime = null;
                $arrTime = null;

                if (preg_match('/(\d{1,2}\s*[-\/]\s*[A-Za-z]{3}\s*[-\/]\s*\d{4}|\d{4}-\d{2}-\d{2})/', $rest, $dm)) {
                    $depDate = $this->parseDate($dm[1]);
                }
                if (preg_match('/(\d{1,2}:\d{2})\s+(\d{1,2}:\d{2})/', $rest, $tm)) {
                    $depTime = $tm[1];
                    $arrTime = $tm[2];
                }

                $itins[] = array_filter([
                    'srno' => $sr++,
                    'airline_code' => $m[1],
                    'airline_no' => $m[2],
                    'departure_airport' => $m[3],
                    'arival_airport' => $m[4],
                    'departure_date' => $depDate,
                    'departure_time' => $depTime,
                    'arrival_time' => $arrTime,
                ], fn ($v) => $v !== null && $v !== '');
            }
        }

        return $itins;
    }

    private function extractPassengers(string $text): array
    {
        // Some PDFs contain "Passenger(s):" lists. This sample doesn't, so keep best-effort.
        $section = $this->extractSection($text, 'PASSENGER', ['FLIGHT DETAILS', 'HOTEL DETAILS:', 'PRICINGS', 'IMPORTANT NOTES:']);
        if (!$section) {
            return [];
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", $section)), fn ($l) => $l !== ''));
        $names = [];

        foreach ($lines as $line) {
            // simple "Mr John Doe" parsing
            if (preg_match('/^(Mr|Mrs|Ms|Miss|Mstr)\s+([A-Za-z]+)\s+([A-Za-z]+)(?:\s+([A-Za-z]+))?$/i', $line, $m)) {
                $names[] = array_filter([
                    'title' => ucfirst(strtolower($m[1])),
                    'fname' => $m[2],
                    'mname' => $m[4] ?? null,
                    'lname' => $m[3],
                ], fn ($v) => $v !== null && $v !== '');
            }
        }

        return $names;
    }

    private function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        $raw = preg_replace('/\s+/', ' ', $raw) ?? $raw;
        $raw = str_replace(["\t"], ' ', $raw);

        $candidates = [
            $raw,
            str_replace(' -', '-', str_replace('- ', '-', $raw)),
            preg_replace('/\s*-\s*/', '-', $raw) ?? $raw,
        ];

        foreach ($candidates as $s) {
            try {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
                    return Carbon::createFromFormat('Y-m-d', $s)->format('Y-m-d');
                }
                if (preg_match('/^\d{1,2}-[A-Za-z]{3}-\d{4}$/', $s)) {
                    return Carbon::createFromFormat('d-M-Y', $s)->format('Y-m-d');
                }
                if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $s)) {
                    return Carbon::createFromFormat('d/m/Y', $s)->format('Y-m-d');
                }
                if (preg_match('/^\d{1,2}\s*-\s*[A-Za-z]{3}\s*-\s*\d{4}$/', $raw)) {
                    $clean = preg_replace('/\s*-\s*/', '-', $raw) ?? $raw;
                    return Carbon::createFromFormat('d-M-Y', $clean)->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return null;
    }
}

