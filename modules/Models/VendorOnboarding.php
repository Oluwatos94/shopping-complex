<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ModulesShoppingComplex\Models\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\ModuleTraits\HasTableName;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $legal_entity_name
 * @property string|null $business_category
 * @property string|null $tax_identification_number
 * @property string|null $physical_address
 * @property string|null $bank_name
 * @property string|null $bank_branch
 * @property string|null $account_number
 * @property string|null $swift_bic_code
 * @property string|null $certificate_of_incorporation
 * @property string|null $government_issued_id
 * @property string|null $proof_of_address
 * @property VendorOnboardingStatusEnum $status
 * @property int $current_step
 * @property bool $agreed_to_terms
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $rejection_reason
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class VendorOnboarding extends Model
{
    use HasFactory, HasTableName;

    /** {@inheritdoc} */
    protected $fillable = [
        'user_id',
        'legal_entity_name',
        'business_category',
        'tax_identification_number',
        'physical_address',
        'bank_name',
        'bank_branch',
        'account_number',
        'swift_bic_code',
        'certificate_of_incorporation',
        'government_issued_id',
        'proof_of_address',
        'status',
        'current_step',
        'agreed_to_terms',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    /** {@inheritdoc} */
    protected $casts = [
        'status' => VendorOnboardingStatusEnum::class,
        'current_step' => 'integer',
        'agreed_to_terms' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isDraft(): bool
    {
        return $this->status === VendorOnboardingStatusEnum::DRAFT;
    }

    public function isPendingReview(): bool
    {
        return $this->status === VendorOnboardingStatusEnum::PENDING_REVIEW;
    }

    public function isApproved(): bool
    {
        return $this->status === VendorOnboardingStatusEnum::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === VendorOnboardingStatusEnum::REJECTED;
    }
}
