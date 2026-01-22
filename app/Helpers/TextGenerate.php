<?php

namespace App\Helpers;

class TextGenerate
{
    public static function short(string $text, int $maxLength = 50, string $suffix = '...'): string
    {
        return mb_strlen($text) > $maxLength
            ? mb_substr($text, 0, $maxLength) . $suffix
            : $text;
    }

    public static function smart(
        string $text,
        int $maxWordLength = 15,
        int $maxTotalLength = 40,
        string $suffix = '...'
    ): string {
        $words = preg_split('/\s+/', trim($text));
        $processedWords = [];

        foreach ($words as $word) {
            if (mb_strlen($word) > $maxWordLength) {
                $processedWords[] = mb_substr($word, 0, $maxWordLength) . $suffix;
            } else {
                $processedWords[] = $word;
            }
        }

        $result = implode(' ', $processedWords);

        if (mb_strlen($result) > $maxTotalLength) {
            return mb_substr($result, 0, $maxTotalLength) . $suffix;
        }

        return $result;
    }

    public static function smartTail(
        string $text,
        int $maxWordLength = 15,
        int $maxTotalLength = 40,
        int $tailLength = 10,
        string $suffix = '...'
    ): string {
        // 🔹 Step 1: potong per kata
        $words = preg_split('/\s+/', trim($text));
        $processedWords = [];

        foreach ($words as $word) {
            if (mb_strlen($word) > $maxWordLength) {
                $processedWords[] = mb_substr($word, 0, $maxWordLength) . $suffix;
            } else {
                $processedWords[] = $word;
            }
        }

        $result = implode(' ', $processedWords);

        // 🔹 Step 2: potong total dengan tail
        if (mb_strlen($result) > $maxTotalLength) {
            $headLength = $maxTotalLength - ($tailLength + mb_strlen($suffix));

            if ($headLength < 0) {
                return mb_substr($result, -$tailLength);
            }

            $head = mb_substr($result, 0, $headLength);
            $tail = mb_substr($result, -$tailLength);

            return $head . $suffix . $tail;
        }

        return $result;
    }
}
