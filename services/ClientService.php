
<?php

require_once __DIR__ . '/../repositories/ClientRepository.php';

class ClientService
{
    private ClientRepository $repository;

    public function __construct(ClientRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getDashboardStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    public function getClients(string $search = ''): array
    {
        $search = trim($search);

        if (mb_strlen($search) > 100) {
            $search = mb_substr($search, 0, 100);
        }

        return $this->repository->searchClients($search);
    }

    public function getClientProfile(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->repository->findById($id);
    }

    public function getClientSegment(array $client): string
    {
        $visits = (int) ($client['completed_visits'] ?? 0);

        if ($visits >= 5) {
            return 'Klient besnik';
        }

        if ($visits >= 1) {
            return 'Klient aktiv';
        }

        return 'Klient i ri';
    }

    /**
     * Validon dhe regjistron një klient të ri.
     */
    public function createClient(array $data): array
    {
        $client = $this->validateClientData($data);

        if ($client['errors']) {
            return [
                'success' => false,
                'errors' => $client['errors']
            ];
        }

        try {
            $this->repository->create($client['data']);

            return [
                'success' => true,
                'message' => 'Klienti u regjistrua me sukses.',
                'errors' => []
            ];
        } catch (PDOException $e) {
            error_log('Create client error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors' => ['Ndodhi një gabim gjatë regjistrimit të klientit.']
            ];
        }
    }

    /**
     * Validon dhe përditëson të dhënat e klientit.
     */
    public function updateClient(int $id, array $data): array
    {
        if ($id <= 0 || !$this->repository->findById($id)) {
            return [
                'success' => false,
                'errors' => ['Klienti nuk u gjet.']
            ];
        }

        $client = $this->validateClientData($data);

        if ($client['errors']) {
            return [
                'success' => false,
                'errors' => $client['errors']
            ];
        }

        try {
            $this->repository->update($id, $client['data']);

            return [
                'success' => true,
                'message' => 'Të dhënat e klientit u ndryshuan me sukses.',
                'errors' => []
            ];
        } catch (PDOException $e) {
            error_log('Update client error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors' => ['Ndodhi një gabim gjatë ndryshimit të klientit.']
            ];
        }
    }

    /**
     * Fshin një klient sipas ID-së.
     */
    public function deleteClient(int $id): array
    {
        if ($id <= 0 || !$this->repository->findById($id)) {
            return [
                'success' => false,
                'errors' => ['Klienti nuk u gjet.']
            ];
        }

        try {
            $this->repository->delete($id);

            return [
                'success' => true,
                'message' => 'Klienti u fshi me sukses.',
                'errors' => []
            ];
        } catch (PDOException $e) {
            error_log('Delete client error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors' => [
                    'Klienti nuk mund të fshihet. Mund të ketë termine ose të dhëna të lidhura me të.'
                ]
            ];
        }
    }

    /**
     * Kontrollon dhe pastron të dhënat e formularit.
     */
    private function validateClientData(array $data): array
    {
        $errors = [];

        $emri = trim((string) ($data['emri'] ?? ''));
        $mbiemri = trim((string) ($data['mbiemri'] ?? ''));
        $telefoni = trim((string) ($data['telefoni'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $adresa = trim((string) ($data['adresa'] ?? ''));

        if ($emri === '') {
            $errors[] = 'Emri është i detyrueshëm.';
        } elseif (mb_strlen($emri) > 100) {
            $errors[] = 'Emri nuk mund të jetë më i gjatë se 100 karaktere.';
        }

        if ($mbiemri === '') {
            $errors[] = 'Mbiemri është i detyrueshëm.';
        } elseif (mb_strlen($mbiemri) > 100) {
            $errors[] = 'Mbiemri nuk mund të jetë më i gjatë se 100 karaktere.';
        }

        if ($telefoni !== '' && mb_strlen($telefoni) > 30) {
            $errors[] = 'Numri i telefonit është shumë i gjatë.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Shkruaj një adresë të vlefshme email.';
        } elseif (mb_strlen($email) > 150) {
            $errors[] = 'Email-i nuk mund të jetë më i gjatë se 150 karaktere.';
        }

        if (mb_strlen($adresa) > 255) {
            $errors[] = 'Adresa nuk mund të jetë më e gjatë se 255 karaktere.';
        }

        return [
            'data' => [
                'emri' => $emri,
                'mbiemri' => $mbiemri,
                'telefoni' => $telefoni !== '' ? $telefoni : null,
                'email' => $email !== '' ? $email : null,
                'adresa' => $adresa !== '' ? $adresa : null
            ],
            'errors' => $errors
        ];
    }
}
