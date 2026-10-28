
<?php

require_once __DIR__ . '/../services/ClientService.php';

class ClientController
{
    private ClientService $service;

    public function __construct(ClientService $service)
    {
        $this->service = $service;
    }

    /**
     * Shfaq listën e klientëve dhe statistikat.
     */
    public function index(): array
    {
        $search = trim((string) ($_GET['kerko'] ?? ''));

        $statistics = $this->service->getDashboardStatistics();
        $clients = $this->service->getClients($search);

        foreach ($clients as &$client) {
            $client['segment'] = $this->service->getClientSegment($client);
        }

        unset($client);

        return [
            'statistics' => $statistics,
            'clients' => $clients,
            'search' => $search
        ];
    }

    /**
     * Merr profilin e një klienti sipas ID-së.
     */
    public function show(int $id): ?array
    {
        return $this->service->getClientProfile($id);
    }

    /**
     * Regjistron një klient të ri.
     */
    public function create(array $data): array
    {
        return $this->service->createClient($data);
    }

    /**
     * Përditëson të dhënat e një klienti.
     */
    public function update(int $id, array $data): array
    {
        return $this->service->updateClient($id, $data);
    }

    /**
     * Fshin një klient sipas ID-së.
     */
    public function delete(int $id): array
    {
        return $this->service->deleteClient($id);
    }
}
