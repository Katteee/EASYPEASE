<?php

function change_reservation(PDO $pdo, int $userId, int $bookId, string $action, ?int $reservationId = null): void
{
    if ($userId < 1 || $bookId < 1 || !in_array($action, ['reserve', 'cancel'], true)) {
        throw new DomainException('Neplatný požadavek.', 400);
    }
    if ($action === 'cancel' && ($reservationId === null || $reservationId < 1)) {
        throw new DomainException('Neplatné ID rezervace.', 400);
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id, total_copies, available_copies FROM books WHERE id = ? FOR UPDATE');
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();
        if (!$book) {
            throw new DomainException('Kniha neexistuje.', 404);
        }

        if ($action === 'reserve') {
            $stmt = $pdo->prepare("SELECT id FROM reservations WHERE user_id = ? AND book_id = ? AND status = 'active' FOR UPDATE");
            $stmt->execute([$userId, $bookId]);
            if ($stmt->fetch()) {
                throw new DomainException('Tuto knihu už máte rezervovanou.', 409);
            }
            if ((int)$book['available_copies'] < 1) {
                throw new DomainException('Kniha již není dostupná.', 409);
            }
            $stmt = $pdo->prepare('UPDATE books SET available_copies = available_copies - 1 WHERE id = ? AND available_copies > 0');
            $stmt->execute([$bookId]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Kniha již není dostupná.', 409);
            }
            $stmt = $pdo->prepare("INSERT INTO reservations (user_id, book_id, status) VALUES (?, ?, 'active')");
            $stmt->execute([$userId, $bookId]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM reservations WHERE id = ? AND user_id = ? AND book_id = ? AND status = 'active' FOR UPDATE");
            $stmt->execute([$reservationId, $userId, $bookId]);
            if (!$stmt->fetch()) {
                throw new DomainException('Aktivní rezervace nebyla nalezena.', 409);
            }
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ? AND user_id = ? AND book_id = ? AND status = 'active'");
            $stmt->execute([$reservationId, $userId, $bookId]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Rezervaci nelze zrušit.', 409);
            }
            $stmt = $pdo->prepare('UPDATE books SET available_copies = available_copies + 1 WHERE id = ? AND available_copies < total_copies');
            $stmt->execute([$bookId]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Počet kusů knihy není konzistentní. Kontaktujte knihovnu.', 409);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
