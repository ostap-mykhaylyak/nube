<?php

namespace Ostap\Nube\Helper;

class Utilities
{
    public function generateName(string $domain): string
    {
        // Pulisci il dominio: rimuovi protocollo, www e TLD
        $domain = preg_replace("#^https?://#i", "", $domain);
        $domain = preg_replace("#^www\.#i", "", $domain);
        $parts = explode(".", $domain);
        $base = $parts[0] ?? "";

        // Mantieni solo lettere e converti in minuscolo
        $clean = preg_replace("/[^a-z]/i", "", $base);
        $clean = strtolower($clean);

        if ($clean === "") {
            throw new InvalidArgumentException("Dominio non valido.");
        }

        // Prepara una stringa di input di almeno 10 caratteri per manipolazione
        if (strlen($clean) >= 8) {
            $input = substr($clean, 0, 10);
        } else {
            // Estendi ripetendo il dominio fino a ~10 caratteri
            $input = $clean;
            while (strlen($input) < 10) {
                $input .= $clean;
            }
            $input = substr($input, 0, 10);
        }

        // Converti in array di caratteri
        $chars = str_split($input);
        $len = count($chars);

        // Non toccare i primi 2 caratteri (mantengono identità fonetica)
        for ($i = 2; $i < $len - 1; $i++) {
            // 50% di probabilità di scambiare con il successivo
            if (random_int(0, 1) === 1) {
                // Scambia $chars[$i] e $chars[$i+1]
                $tmp = $chars[$i];
                $chars[$i] = $chars[$i + 1];
                $chars[$i + 1] = $tmp;
                $i++; // salta il prossimo per evitare sovrascrittura
            }
        }

        $shuffled = implode("", $chars);

        // Assicura esattamente 8 caratteri
        if (strlen($shuffled) >= 8) {
            $result = substr($shuffled, 0, 8);
        } else {
            // Riempi con vocali cicliche se troppo corto (raro)
            $vowels = "aeiou";
            while (strlen($shuffled) < 8) {
                $shuffled .= $vowels[strlen($shuffled) % strlen($vowels)];
            }
            $result = substr($shuffled, 0, 8);
        }

        // Ulteriore sicurezza: solo lettere minuscole
        $result = preg_replace("/[^a-z]/", "", strtolower($result));
        return substr($result, 0, 8);
    }
}
