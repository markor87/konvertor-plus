<?php

declare(strict_types=1);

namespace App\Services;

class TextConverterService
{
    // Izuzeci za digraf "dj" - ne konvertuju se u "đ"
    private array $exceptDj = [
        'adjektiv', 'adjunkt', 'bazdje', 'bdje', 'bezdje', 'blijedje', 'bludje', 'bridje',
        'vidjel', 'vidjet', 'vindjakn', 'višenedje', 'vrijedje', 'gdje', 'gudje', 'gdjir',
        'daždje', 'dvonedje', 'devetonedje', 'desetonedje', 'djb', 'djeva', 'djevi', 'djevo',
        'djed', 'djejstv', 'djel', 'djenem', 'djeneš', 'djenu', 'djet', 'djec', 'dječ',
        'djuar', 'djubison', 'djubouz', 'djuer', 'djui', 'djuks', 'djulej', 'djumars',
        'djupont', 'djurant', 'djusenberi', 'djuharst', 'djuherst', 'dovdje', 'dogrdje',
        'dodjel', 'drvodje', 'drugdje', 'elektrosnabdje', 'žudje', 'zabludje', 'zavidje',
        'zavrijedje', 'zagudje', 'zadjev', 'zadjen', 'zalebdje', 'zaludje', 'zaodje',
        'zapodje', 'zarudje', 'zasjedje', 'zasmrdje', 'zastidje', 'zaštedje', 'zdje',
        'zlodje', 'igdje', 'izbledje', 'izblijedje', 'izvidje', 'izdjejst', 'izdjelj',
        'izludje', 'isprdje', 'jednonedje', 'kojegdje', 'kudjelj', 'lebdje', 'ludjel',
        'ludjet', 'makfadjen', 'marmadjuk', 'međudjel', 'nadjaha', 'nadjača', 'nadjeb',
        'nadjev', 'nadjenul', 'nadjenuo', 'nadjenut', 'negdje', 'nedjel', 'nadjunač',
        'nenadjača', 'nenavidje', 'neodje', 'nepodjarm', 'nerazdje', 'nigdje', 'obdjel',
        'obnevidje', 'ovdje', 'odjav', 'odjah', 'odjaš', 'odjeb', 'odjev', 'odjed',
        'odjezd', 'odjek', 'odjel', 'odjen', 'odjeć', 'odjec', 'odjur', 'odsjedje',
        'ondje', 'opredje', 'osijedje', 'osmonedje', 'pardju', 'perdju', 'petonedje',
        'poblijedje', 'povidje', 'pogdjegdje', 'pogdje', 'podjakn', 'podjamč', 'podjemč',
        'podjar', 'podjeb', 'podjebrad', 'podjed', 'podjezič', 'podjel', 'podjen',
        'podjet', 'pododjel', 'pozavidje', 'poludje', 'poljodjel', 'ponegdje', 'ponedjelj',
        'porazdje', 'posijedje', 'posjedje', 'postidje', 'potpodjel', 'poštedje', 'pradjed',
        'prdje', 'preblijedje', 'previdje', 'predvidje', 'predjel', 'preodjen', 'preraspodje',
        'presjedje', 'pridjev', 'pridjen', 'prismrdje', 'prištedje', 'probdje', 'problijedje',
        'prodjen', 'prolebdje', 'prosijedje', 'prosjedje', 'protivdjel', 'prošlonedje',
        'razvidje', 'razdjev', 'razdjel', 'razodje', 'raspodje', 'rasprdje', 'remekdjel',
        'rudjen', 'rudjet', 'sadje', 'svagdje', 'svidje', 'svugdje', 'sedmonedjelj',
        'sijedje', 'sjedje', 'smrdje', 'snabdje', 'snovidje', 'starosjedje', 'stidje',
        'studje', 'sudjel', 'tronedje', 'ublijedje', 'uvidje', 'udjel', 'udjen', 'uprdje',
        'usidjel', 'usjedje', 'usmrdje', 'uštedje', 'cjelonedje', 'četvoronedje', 'čukundjed',
        'šestonedjelj', 'štedje', 'štogdje', 'šukundjed'
    ];

    // Izuzeci za digraf "dž" - ne konvertuju se u "џ"
    private array $exceptDž = [
        'feldžandarm', 'nadžanj', 'nadždrel', 'nadžel', 'nadžeo', 'nadžet', 'nadživ',
        'nadžinj', 'nadžnj', 'nadžrec', 'nadžup', 'odžali', 'odžari', 'odžel', 'odžive',
        'odživljava', 'odžubor', 'odžvaka', 'odžval', 'odžvać', 'podžanr', 'podžel',
        'podže', 'podžig', 'podžiz', 'podžil', 'podžnje', 'podžupan', 'predželu', 'predživot'
    ];

    // Izuzeci za digraf "nj" - ne konvertuju se u "њ"
    private array $exceptNj = [
        'anjon', 'injaric', 'injekc', 'injekt', 'injicira', 'injurij', 'kenjon',
        'konjug', 'konjunk', 'nekonjug', 'nekonjunk', 'ssrnj', 'tanjug', 'vanjezičk'
    ];

    /**
     * Konvertuje tekst iz ćirilice u latinicu
     */
    public function convertToLatinica(string $input): string
    {
        $map = [
            // Mala ćirilična slova
            'ђ' => 'dj', 'њ' => 'nj', 'љ' => 'lj',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ж' => 'ž', 'з' => 'z', 'и' => 'i', 'ј' => 'j',
            'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'ћ' => 'ć',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'č',
            'џ' => 'dž', 'ш' => 'š',
            // Velika ćirilična slova
            'Ђ' => 'Dj', 'Њ' => 'Nj', 'Љ' => 'Lj',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ж' => 'Ž', 'З' => 'Z', 'И' => 'I', 'Ј' => 'J',
            'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
            'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'Ћ' => 'Ć',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'Č',
            'Џ' => 'Dž', 'Ш' => 'Š'
        ];

        return strtr($input, $map);
    }

    /**
     * Konvertuje tekst iz latinice u ćirilicu
     */
    public function convertToCirilica(string $input): string
    {
        // Puna mapa za konverziju
        $fullMap = [
            'dj' => 'ђ', 'nj' => 'њ', 'lj' => 'љ',
            'Dj' => 'Ђ', 'Nj' => 'Њ', 'Lj' => 'Љ',
            'A' => 'А', 'B' => 'Б', 'C' => 'Ц', 'Č' => 'Ч', 'Ć' => 'Ћ',
            'D' => 'Д', 'Dž' => 'Џ', 'E' => 'Е', 'F' => 'Ф', 'G' => 'Г', 'H' => 'Х',
            'I' => 'И', 'J' => 'Ј', 'K' => 'К', 'L' => 'Л', 'M' => 'М', 'N' => 'Н',
            'O' => 'О', 'P' => 'П', 'R' => 'Р', 'S' => 'С', 'Š' => 'Ш', 'T' => 'Т',
            'U' => 'У', 'V' => 'В', 'Z' => 'З', 'Ž' => 'Ж',
            'a' => 'а', 'b' => 'б', 'c' => 'ц', 'č' => 'ч', 'ć' => 'ћ',
            'd' => 'д', 'dž' => 'џ', 'e' => 'е', 'f' => 'ф', 'g' => 'г', 'h' => 'х',
            'i' => 'и', 'j' => 'ј', 'k' => 'к', 'l' => 'л', 'm' => 'м', 'n' => 'н',
            'o' => 'о', 'p' => 'п', 'r' => 'р', 's' => 'с', 'š' => 'ш', 't' => 'т',
            'u' => 'у', 'v' => 'в', 'z' => 'з', 'ž' => 'ж'
        ];

        // Mapa bez digrafa za izuzetke
        $mapNoDigraphs = array_filter($fullMap, function($key) {
            $lower = mb_strtolower($key);
            return !in_array($lower, ['dj', 'nj', 'dž']);
        }, ARRAY_FILTER_USE_KEY);

        // Podela teksta na reči i ostalo (interpunkcija, razmaci, itd.)
        $parts = preg_split('/(\W+)/u', $input, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $i => $token) {
            // Preskoči sve što nije reč
            if (!preg_match('/^\w+$/u', $token)) {
                continue;
            }

            $lowerToken = mb_strtolower($token);
            $isException = false;

            // Proveri da li reč počinje nekim od izuzetaka
            foreach ($this->exceptDj as $except) {
                if (mb_strpos($lowerToken, $except) === 0) {
                    $isException = true;
                    break;
                }
            }

            if (!$isException) {
                foreach ($this->exceptDž as $except) {
                    if (mb_strpos($lowerToken, $except) === 0) {
                        $isException = true;
                        break;
                    }
                }
            }

            if (!$isException) {
                foreach ($this->exceptNj as $except) {
                    if (mb_strpos($lowerToken, $except) === 0) {
                        $isException = true;
                        break;
                    }
                }
            }

            // Odaberi odgovarajuću mapu
            $map = $isException ? $mapNoDigraphs : $fullMap;

            // Sortiraj po dužini ključa (najduži prvi) da bi prvo konvertovao digrafe
            $sortedMap = $map;
            uksort($sortedMap, function($a, $b) {
                return mb_strlen($b) - mb_strlen($a);
            });

            // Konvertuj token
            $converted = $token;
            foreach ($sortedMap as $from => $to) {
                $converted = str_replace($from, $to, $converted);
            }

            $parts[$i] = $converted;
        }

        return implode('', $parts);
    }
}
