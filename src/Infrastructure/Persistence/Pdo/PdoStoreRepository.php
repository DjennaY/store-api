<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Pdo;

use App\Application\Store\Repository\StoreRepositoryInterface;
use App\Domain\Store\Criteria\StoreCriteria;
use App\Domain\Store\Entity\Store;
use App\Domain\Store\Exception\CouldNotDeleteStoreException;
use App\Domain\Store\Exception\CouldNotFetchStoreException;
use App\Domain\Store\Exception\CouldNotSaveStoreException;
use App\Domain\Store\ValueObject\NaturalKey;
use App\Domain\Store\ValueObject\StoreAddress;
use App\Domain\Store\ValueObject\StoreId;
use App\Domain\Store\ValueObject\StoreName;
use App\Domain\User\ValueObject\UserId;
use PDO;

final class PdoStoreRepository implements StoreRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(StoreId $id): ?Store
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM stores WHERE id = :id');
            $stmt->execute(['id' => $id->getValue()]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? $this->hydrate($row) : null;
        } catch (\Throwable $e) {
            throw new CouldNotFetchStoreException('findById', $e);
        }
    }

    public function findByNaturalKey(NaturalKey $key): ?Store
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM stores WHERE natural_key = :key AND deleted_at IS NULL'
            );
            $stmt->execute(['key' => $key->getValue()]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? $this->hydrate($row) : null;
        } catch (\Throwable $e) {
            throw new CouldNotFetchStoreException('findByNaturalKey', $e);
        }
    }

    public function findAll(StoreCriteria $criteria): array
    {
        try {
            $allowed = ['name', 'city', 'country_iso', 'created_at', 'updated_at'];
            $sortBy = in_array($criteria->sortBy, $allowed, true) ? $criteria->sortBy : 'created_at';
            $order = strtoupper($criteria->sortOrder) === 'ASC' ? 'ASC' : 'DESC';

            $conditions = ['deleted_at IS NULL'];
            $params = [];

            if ($criteria->name !== null) {
                $conditions[] = 'name LIKE :name';
                $params['name'] = '%' . $criteria->name . '%';
            }
            if ($criteria->city !== null) {
                $conditions[] = 'city LIKE :city';
                $params['city'] = '%' . $criteria->city . '%';
            }
            if ($criteria->countryIso !== null) {
                $conditions[] = 'country_iso = :country_iso';
                $params['country_iso'] = strtoupper($criteria->countryIso);
            }

            $where = 'WHERE ' . implode(' AND ', $conditions);
            $sql = "SELECT * FROM stores {$where} ORDER BY {$sortBy} {$order} LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue('limit', $criteria->limit, PDO::PARAM_INT);
            $stmt->bindValue('offset', $criteria->offset, PDO::PARAM_INT);
            $stmt->execute();

            return array_map(
                fn (array $row) => $this->hydrate($row),
                $stmt->fetchAll(PDO::FETCH_ASSOC)
            );
        } catch (\Throwable $e) {
            throw new CouldNotFetchStoreException('findAll', $e);
        }
    }

    public function save(Store $store): void
    {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO stores
                    (id, name, address, city, zip_code, country_iso, phone, created_by, natural_key, created_at, updated_at, deleted_at)
                VALUES
                    (:id, :name, :address, :city, :zip_code, :country_iso, :phone, :created_by, :natural_key, :created_at, :updated_at, :deleted_at)
                ON DUPLICATE KEY UPDATE
                    name        = VALUES(name),
                    address     = VALUES(address),
                    city        = VALUES(city),
                    zip_code    = VALUES(zip_code),
                    country_iso = VALUES(country_iso),
                    phone       = VALUES(phone),
                    natural_key = VALUES(natural_key),
                    updated_at  = VALUES(updated_at),
                    deleted_at  = VALUES(deleted_at)
            ');
            $stmt->execute([
                'id' => $store->getId()->getValue(),
                'name' => $store->getName()->getValue(),
                'address' => $store->getAddress()->getStreet(),
                'city' => $store->getAddress()->getCity(),
                'zip_code' => $store->getAddress()->getZipCode(),
                'country_iso' => $store->getAddress()->getCountryIso(),
                'phone' => $store->getPhone(),
                'created_by' => $store->getCreatedBy()->getValue(),
                'natural_key' => $store->getNaturalKey()->getValue(),
                'created_at' => $store->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $store->getUpdatedAt()->format('Y-m-d H:i:s'),
                'deleted_at' => $store->getDeletedAt()?->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            throw new CouldNotSaveStoreException('save', $e);
        }
    }

    public function softDelete(StoreId $id): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE stores SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id'
            );
            $stmt->execute(['id' => $id->getValue()]);
        } catch (\Throwable $e) {
            throw new CouldNotDeleteStoreException($id->getValue(), $e);
        }
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Store
    {
        return Store::reconstitute(
            id: new StoreId($row['id']),
            name: new StoreName($row['name']),
            address: new StoreAddress($row['address'], $row['city'], $row['zip_code'], $row['country_iso']),
            phone: $row['phone'],
            createdBy: new UserId($row['created_by']),
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
            deletedAt: $row['deleted_at'] !== null ? new \DateTimeImmutable($row['deleted_at']) : null,
        );
    }
}
