<?php
// Per the teacher's requirement: no auto-increment primary keys anywhere.
// Every table's SERIAL default was dropped (see migrations/014), so the
// app must compute the next id itself before every insert, same idea as
// the Farmer.php teaching convention of app-assigned keys.
//
// $table/$pk_column are always fixed string literals from our own model
// code (never user input), so building the query by concatenation here
// carries no injection risk — same pattern already used elsewhere in this
// codebase (e.g. Conversation::setTyping's column-name switch).
class IdGenerator {
    public static function next(PDO $pdo, string $table, string $pk_column): int {
        $stmt = $pdo->query("SELECT COALESCE(MAX({$pk_column}), 0) + 1 FROM {$table}");
        return (int) $stmt->fetchColumn();
    }
}
