<?php

function change_reservation(PDO $pdo, int $userId, int $bookId, string $action, ?int $reservationId = null): void
{
    if ($userId < 1 || $bookId < 1 || !in_array($action, ['reserve', 'cancel', 'complete'], true)) {
        throw new DomainException('Neplatný požadavek.', 400);
    }
    if ($action !== 'reserve' && ($reservationId === null || $reservationId < 1)) {
        throw new DomainException('Neplatné ID rezervace.', 400);
    }
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $role = $stmt->fetchColumn();
        if ($role === false) throw new DomainException('Přihlaste se znovu.', 401);
        if ($action === 'complete' && $role !== 'admin') throw new DomainException('Rezervaci může dokončit pouze administrátor.', 403);

        $stmt = $pdo->prepare('SELECT id, total_copies, available_copies FROM books WHERE id = ? FOR UPDATE');
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();
        if (!$book) throw new DomainException('Kniha neexistuje.', 404);
        $available = (int)$book['available_copies'];
        $total = (int)$book['total_copies'];
        if ($total < 0 || $available < 0 || $available > $total) throw new DomainException('Počet kusů knihy není konzistentní. Kontaktujte knihovnu.', 409);

        if ($action === 'reserve') {
            $stmt = $pdo->prepare("SELECT id, user_id FROM reservations WHERE book_id = ? AND status = 'active' FOR UPDATE");
            $stmt->execute([$bookId]);
            $active = $stmt->fetchAll();
            foreach ($active as $row) {
                if ((int)$row['user_id'] === $userId) throw new DomainException('Tuto knihu už máte rezervovanou.', 409);
            }
            if ($available < 1) throw new DomainException('Kniha již není dostupná.', 409);
            if ($available + count($active) > $total) throw new DomainException('Počet kusů knihy není konzistentní. Kontaktujte knihovnu.', 409);
            $stmt = $pdo->prepare('UPDATE books SET available_copies = available_copies - 1 WHERE id = ? AND available_copies > 0');
            $stmt->execute([$bookId]);
            if ($stmt->rowCount() !== 1) throw new DomainException('Kniha již není dostupná.', 409);
            $stmt = $pdo->prepare("INSERT INTO reservations (user_id, book_id, status) VALUES (?, ?, 'active')");
            $stmt->execute([$userId, $bookId]);
        } else {
            $sql = "SELECT id FROM reservations WHERE id = ? AND book_id = ? AND status = 'active'";
            $params = [$reservationId, $bookId];
            if ($action === 'cancel') { $sql .= ' AND user_id = ?'; $params[] = $userId; }
            $stmt = $pdo->prepare($sql . ' FOR UPDATE');
            $stmt->execute($params);
            if (!$stmt->fetch()) throw new DomainException('Aktivní rezervace nebyla nalezena.', 409);
            $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ? AND status = 'active'");
            $stmt->execute([$action === 'cancel' ? 'cancelled' : 'completed', $reservationId]);
            if ($stmt->rowCount() !== 1) throw new DomainException('Stav rezervace se změnil. Obnovte stránku.', 409);
            $stmt = $pdo->prepare('UPDATE books SET available_copies = available_copies + 1 WHERE id = ? AND available_copies < total_copies');
            $stmt->execute([$bookId]);
            if ($stmt->rowCount() !== 1) throw new DomainException('Počet kusů knihy není konzistentní. Kontaktujte knihovnu.', 409);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
