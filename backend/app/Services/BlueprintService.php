<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\ConflictException;
use App\Models\Blueprint;
use App\Models\BlueprintPhoto;
use App\Repositories\Interfaces\BlueprintPhotoRepositoryInterface;
use App\Repositories\Interfaces\BlueprintRepositoryInterface;
use App\Repositories\Interfaces\EnquiryRepositoryInterface;
use App\Support\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlueprintService
{
    public function __construct(
        private readonly BlueprintRepositoryInterface      $blueprintRepo,
        private readonly BlueprintPhotoRepositoryInterface $blueprintPhotoRepo,
        private readonly EnquiryRepositoryInterface        $enquiryRepo,
    ) {}

    public function getByEnquiryId(int $enquiryId): Blueprint
    {
        $enquiry = $this->enquiryRepo->findById($enquiryId);

        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        $blueprint = $this->blueprintRepo->findByEnquiryId($enquiryId);

        if (! $blueprint) {
            throw new NotFoundHttpException('No blueprint found for this enquiry.');
        }

        return $blueprint;
    }

    public function getByCustomerId(int $customerId): Collection
    {
        return $this->blueprintRepo->findByCustomerId($customerId);
    }

    public function create(int $enquiryId, array $data): Blueprint
    {
        $enquiry = $this->enquiryRepo->findById($enquiryId);

        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        if ($enquiry->blueprint_id) {
            throw new ConflictException('A blueprint already exists for this enquiry.');
        }

        return DB::transaction(function () use ($enquiry, $data) {
            $blueprint = $this->blueprintRepo->create(array_merge($data, ['customer_id' => $enquiry->customer_id]));
            $this->enquiryRepo->update($enquiry, ['blueprint_id' => $blueprint->id]);
            return $blueprint;
        });
    }

    public function update(int $enquiryId, array $data): Blueprint
    {
        $enquiry = $this->enquiryRepo->findById($enquiryId);

        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        $blueprint = $this->blueprintRepo->findByEnquiryId($enquiryId);

        if (! $blueprint) {
            throw new NotFoundHttpException('No blueprint found for this enquiry.');
        }

        return $this->blueprintRepo->update($blueprint, $data);
    }

    public function createForCustomer(int $customerId, array $data): Blueprint
    {
        if (empty($data['towers'])) {
            $data['towers'] = $this->buildTowersJson($data);
        }

        return $this->blueprintRepo->create(array_merge($data, ['customer_id' => $customerId]));
    }

    public function getByIdForCustomer(int $blueprintId, int $customerId): Blueprint
    {
        $blueprint = $this->blueprintRepo->findById($blueprintId);

        if (! $blueprint || $blueprint->customer_id !== $customerId) {
            throw new NotFoundHttpException('Blueprint not found.');
        }

        return $blueprint;
    }

    public function updateForCustomer(int $blueprintId, int $customerId, array $data): Blueprint
    {
        $blueprint = $this->blueprintRepo->findById($blueprintId);

        if (! $blueprint || $blueprint->customer_id !== $customerId) {
            throw new NotFoundHttpException('Blueprint not found.');
        }

        return $this->blueprintRepo->update($blueprint, $data);
    }

    public function deleteForCustomer(int $blueprintId, int $customerId): void
    {
        $blueprint = $this->blueprintRepo->findById($blueprintId);

        if (! $blueprint || $blueprint->customer_id !== $customerId) {
            throw new NotFoundHttpException('Blueprint not found.');
        }

        $this->blueprintRepo->delete($blueprint);
    }

    public function generateForDraft(int $customerId, array $data): Blueprint
    {
        $enquiryId = $data['enquiry_id'];
        $enquiry   = $this->enquiryRepo->findById($enquiryId);

        if (! $enquiry || $enquiry->customer_id !== $customerId) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        if ($enquiry->status !== ProjectStatus::Draft) {
            throw new BusinessRuleException('Blueprint can only be created for a draft enquiry.');
        }

        if ($enquiry->blueprint_id) {
            throw new ConflictException('A blueprint already exists for this enquiry.');
        }

        $blueprintData = array_diff_key($data, ['enquiry_id' => true]);

        if (empty($blueprintData['towers'])) {
            $blueprintData['towers'] = $this->buildTowersJson($blueprintData);
        }

        return DB::transaction(function () use ($enquiry, $blueprintData, $customerId) {
            $blueprint = $this->blueprintRepo->create(array_merge($blueprintData, ['customer_id' => $customerId]));
            $this->enquiryRepo->update($enquiry, ['blueprint_id' => $blueprint->id]);
            return $blueprint;
        });
    }

    private function buildTowersJson(array $data): array
    {
        $towerCount         = (int) ($data['tower_count'] ?? 0);
        $floorsPerTower     = (int) ($data['floors_per_tower'] ?? 0);
        $flatsPerFloor      = (int) ($data['flats_per_floor'] ?? 0);
        $includeGroundFloor = (bool) ($data['include_ground_floor'] ?? false);
        $parkingFloors      = (int) ($data['parking_floors'] ?? 0);
        $officeFloors       = (int) ($data['office_floors'] ?? 0);

        $towers = [];

        for ($t = 1; $t <= $towerCount; $t++) {
            $floors = [];

            // Basement / parking floors — deepest first, no flats
            for ($p = $parkingFloors; $p >= 1; $p--) {
                $floors[] = [
                    'level'  => -$p,
                    'label'  => "Basement B{$p}",
                    'type'   => 'parking',
                    'flats'  => [],
                ];
            }

            // Ground floor
            if ($includeGroundFloor) {
                $flats = [];
                for ($f = 1; $f <= $flatsPerFloor; $f++) {
                    $flats[] = ['name' => sprintf('G%02d', $f), 'apertures' => []];
                }
                $floors[] = [
                    'level'  => 0,
                    'label'  => 'Ground Floor',
                    'type'   => 'residential',
                    'flats'  => $flats,
                ];
            }

            // Numbered floors — top $officeFloors are office type
            $residentialCount = max(0, $floorsPerTower - $officeFloors);

            for ($fl = 1; $fl <= $floorsPerTower; $fl++) {
                $type  = ($fl > $residentialCount) ? 'office' : 'residential';
                $flats = [];
                for ($f = 1; $f <= $flatsPerFloor; $f++) {
                    $flats[] = ['name' => sprintf('%d%02d', $fl, $f), 'apertures' => []];
                }
                $floors[] = [
                    'level'  => $fl,
                    'label'  => "Floor {$fl}",
                    'type'   => $type,
                    'flats'  => $flats,
                ];
            }

            $towers[] = [
                'name'   => "Tower {$t}",
                'floors' => $floors,
            ];
        }

        return $towers;
    }

    public function updateForDraft(int $enquiryId, int $customerId, array $data): Blueprint
    {
        $enquiry = $this->enquiryRepo->findById($enquiryId);

        if (! $enquiry || $enquiry->customer_id !== $customerId) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        if ($enquiry->status !== ProjectStatus::Draft) {
            throw new ConflictException('Blueprint can only be edited while the enquiry is in draft.');
        }

        $blueprint = $this->blueprintRepo->findByEnquiryId($enquiryId);

        if (! $blueprint) {
            throw new NotFoundHttpException('No blueprint found for this enquiry.');
        }

        return $this->blueprintRepo->update($blueprint, $data);
    }

    public function createForAdmin(int $customerId, array $data): Blueprint
    {
        if (empty($data['towers'])) {
            $data['towers'] = $this->buildTowersJson($data);
        }

        return $this->blueprintRepo->create(array_merge($data, ['customer_id' => $customerId]));
    }

    public function updateById(int $blueprintId, array $data): Blueprint
    {
        $blueprint = $this->blueprintRepo->findById($blueprintId);

        if (! $blueprint) {
            throw new NotFoundHttpException('Blueprint not found.');
        }

        return $this->blueprintRepo->update($blueprint, $data);
    }

    public function deleteById(int $blueprintId): void
    {
        $blueprint = $this->blueprintRepo->findById($blueprintId);

        if (! $blueprint) {
            throw new NotFoundHttpException('Blueprint not found.');
        }

        $this->blueprintRepo->delete($blueprint);
    }

    /**
     * Upload one or more photos, scoped to an enquiry/booking.
     * Pass $customerOwnerId when called from a customer context to enforce ownership.
     *
     * @param  UploadedFile[]  $files
     */
    public function uploadPhotos(int $enquiryId, array $files, string $uploaderType, int $uploaderId, ?int $customerOwnerId = null): Collection
    {
        $enquiry = $this->enquiryRepo->findById($enquiryId);

        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        if ($customerOwnerId !== null && $enquiry->customer_id !== $customerOwnerId) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        if (! $enquiry->blueprint_id) {
            throw new NotFoundHttpException('No blueprint found for this enquiry.');
        }

        $photos = new \Illuminate\Database\Eloquent\Collection();

        foreach ($files as $file) {
            $path = $this->storeFile($enquiryId, $file);

            $photo = $this->blueprintPhotoRepo->create([
                'enquiry_id'       => $enquiryId,
                'file_path'        => $path,
                'original_name'    => $file->getClientOriginalName(),
                'uploaded_by_type' => $uploaderType,
                'uploaded_by_id'   => $uploaderId,
            ]);

            $photos->push($photo);
        }

        return $photos;
    }

    public function getPhotos(int $enquiryId, ?int $customerOwnerId = null): Collection
    {
        $enquiry = $this->enquiryRepo->findById($enquiryId);

        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        if ($customerOwnerId !== null && $enquiry->customer_id !== $customerOwnerId) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        if (! $enquiry->blueprint_id) {
            throw new NotFoundHttpException('No blueprint found for this enquiry.');
        }

        return $this->blueprintPhotoRepo->findByEnquiryId($enquiryId);
    }

    public function deletePhoto(int $enquiryId, int $photoId, ?int $customerOwnerId = null): void
    {
        $enquiry = $this->enquiryRepo->findById($enquiryId);

        if (! $enquiry) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        if ($customerOwnerId !== null && $enquiry->customer_id !== $customerOwnerId) {
            throw new NotFoundHttpException('Enquiry not found.');
        }

        if (! $enquiry->blueprint_id) {
            throw new NotFoundHttpException('No blueprint found for this enquiry.');
        }

        $photo = $this->blueprintPhotoRepo->findById($photoId);

        if (! $photo || $photo->enquiry_id !== $enquiryId) {
            throw new NotFoundHttpException('Photo not found.');
        }

        Storage::disk('cloud')->delete($photo->file_path);
        $this->blueprintPhotoRepo->delete($photo);
    }

    private function storeFile(int $enquiryId, UploadedFile $file): string
    {
        $ext       = strtolower($file->getClientOriginalExtension());
        $safeName  = preg_replace('/[^a-z0-9_\-]/', '_', strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)));
        $filename  = time() . '_' . $safeName . '.' . $ext;
        $directory = "blueprints/{$enquiryId}/photos";

        Storage::disk('cloud')->putFileAs($directory, $file, $filename);

        return "{$directory}/{$filename}";
    }
}
