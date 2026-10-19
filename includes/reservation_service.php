<?php

function change_reservation(PDO $pdo, int $userId, int $bookId, string $action): void
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id, total_copies, available_copies FROM books WHERE id = ? FOR UPDATE');
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();
        if (!$book) throw new DomainException('Kniha neexistuje.');

        $stmt = $pdo->prepare("SELECT id FROM reservations WHERE user_id = ? AND book_id = ? AND status = 'active' FOR UPDATE");
        $stmt->execute([$userId, $bookId]);
        $active = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($action === 'reserve') {
            if ($active) throw new DomainException('Tuto knihu už máte rezervovanou.');
            if ((int)$book['available_copies'] < 1) throw new DomainException('Kniha již není dostupná.');
            $stmt = $pdo->prepare('UPDATE books SET available_copies = available_copies - 1 WHERE id = ? AND available_copies > 0');
            $stmt->execute([$bookId]);
            if ($stmt->rowCount() !== 1) throw new DomainException('Kniha již není dostupná.');
            $stmt = $pdo->prepare("INSERT INTO reservations (user_id, book_id, status) VALUES (?, ?, 'active')");
            $stmt->execute([$userId, $bookId]);
        } elseif ($action === 'cancel') {
            if (!$active) throw new DomainException('Aktivní rezervace nebyla nalezena.');

            $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE user_id = ? AND book_id = ? AND status = 'active'");
            $stmt->execute([$userId, $bookId]);
            $released = $stmt->rowCount();
            $stmt = $pdo->prepare('UPDATE books SET available_copies = LEAST(total_copies, available_copies + ?) WHERE id = ?');
            $stmt->execute([$released, $bookId]);
        } else {
            throw new DomainException('Neplatná akce.');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
