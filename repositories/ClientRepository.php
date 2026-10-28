
<?php

class ClientRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Merr statistikat e përgjithshme të klientëve.
     */
    public function getStatistics(): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total_clients,
                COALESCE(
                    SUM(
                        CASE
                            WHEN data_regjistrimit >=
                                DATE_FORMAT(CURDATE(), '%Y-%m-01')
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS new_this_month
            FROM klientet
        ";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_clients' => 0,
            'new_this_month' => 0
        ];
    }

    /**
     * Kërkon klientët dhe merr të dhënat e vizitave dhe pagesave.
     */
    public function searchClients(string $search = ''): array
    {
        $sql = "
            SELECT
                k.klient_id,
                k.emri,
                k.mbiemri,
                k.telefoni,
                k.email,
                k.adresa,
                k.data_regjistrimit,

                COALESCE(
                    v.completed_visits,
                    0
                ) AS completed_visits,

                v.last_visit,

                COALESCE(
                    p.total_paid,
                    0
                ) AS total_paid

            FROM klientet k

            LEFT JOIN (
                SELECT
                    klient_id,
                    COUNT(*) AS completed_visits,
                    MAX(data) AS last_visit
                FROM terminet
                WHERE statusi = 'Përfunduar'
                GROUP BY klient_id
            ) v
                ON v.klient_id = k.klient_id

            LEFT JOIN (
                SELECT
                    t.klient_id,
                    SUM(pg.shuma) AS total_paid
                FROM terminet t
                INNER JOIN pagesa pg
                    ON pg.termin_id = t.termin_id
                WHERE pg.statusi = 'Paguar'
                GROUP BY t.klient_id
            ) p
                ON p.klient_id = k.klient_id
        ";

        if ($search !== '') {
            $sql .= "
                WHERE k.emri LIKE :search
                   OR k.mbiemri LIKE :search
                   OR k.telefoni LIKE :search
                   OR k.email LIKE :search
            ";
        }

        $sql .= "
            ORDER BY
                k.data_regjistrimit DESC,
                k.klient_id DESC
        ";

        $stmt = $this->pdo->prepare($sql);

        if ($search !== '') {
            $stmt->execute([
                'search' => '%' . $search . '%'
            ]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gjen një klient sipas ID-së.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                klient_id,
                emri,
                mbiemri,
                telefoni,
                email,
                adresa,
                data_regjistrimit
            FROM klientet
            WHERE klient_id = :id
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $id
        ]);

        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        return $client ?: null;
    }

    /**
     * Regjistron një klient të ri.
     */
    public function create(array $client): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO klientet
                (emri, mbiemri, telefoni, email, adresa)
            VALUES
                (:emri, :mbiemri, :telefoni, :email, :adresa)
        ");

        return $stmt->execute([
            'emri' => $client['emri'],
            'mbiemri' => $client['mbiemri'],
            'telefoni' => $client['telefoni'] ?: null,
            'email' => $client['email'] ?: null,
            'adresa' => $client['adresa'] ?: null
        ]);
    }

    /**
     * Përditëson të dhënat e një klienti.
     */
    public function update(int $id, array $client): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE klientet
            SET
                emri = :emri,
                mbiemri = :mbiemri,
                telefoni = :telefoni,
                email = :email,
                adresa = :adresa
            WHERE klient_id = :id
        ");

        return $stmt->execute([
            'emri' => $client['emri'],
            'mbiemri' => $client['mbiemri'],
            'telefoni' => $client['telefoni'] ?: null,
            'email' => $client['email'] ?: null,
            'adresa' => $client['adresa'] ?: null,
            'id' => $id
        ]);
    }

    /**
     * Fshin një klient sipas ID-së.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM klientet
            WHERE klient_id = :id
        ");

        return $stmt->execute([
            'id' => $id
        ]);
    }
}


