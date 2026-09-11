<?php

namespace Modules\Treasury\Entities;

use App\Models\BankAccount;
use App\Models\CompanyBilletera;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class TreasuryAccount extends Model
{
    use HasFactory;

    public const TYPE_BANK = 'bank';
    public const TYPE_WALLET = 'wallet';

    protected $fillable = [
        'type',
        'bank_account_id',
        'company_billetera_id',
        'label',
        'currency_type_id',
        'opening_balance',
        'opening_balance_date',
        'status',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
        'status' => 'boolean',
    ];

    /**
     * La coherencia tipo <-> cuenta vinculada se valida aquí (compatible
     * con MySQL/MariaDB antiguos que no aplican CHECK).
     */
    protected static function booted(): void
    {
        static::saving(function (TreasuryAccount $account) {
            $account->validateLink();
        });
    }

    public function validateLink(): void
    {
        if ($this->type === self::TYPE_BANK) {
            if (empty($this->bank_account_id)) {
                throw ValidationException::withMessages([
                    'bank_account_id' => 'Una cuenta de banco requiere una cuenta bancaria vinculada.',
                ]);
            }

            if (filled($this->company_billetera_id)) {
                throw ValidationException::withMessages([
                    'company_billetera_id' => 'Una cuenta de banco no puede tener una billetera vinculada.',
                ]);
            }
        }

        if ($this->type === self::TYPE_WALLET) {
            if (empty($this->company_billetera_id)) {
                throw ValidationException::withMessages([
                    'company_billetera_id' => 'Una billetera requiere una billetera de empresa vinculada.',
                ]);
            }

            if (filled($this->bank_account_id)) {
                throw ValidationException::withMessages([
                    'bank_account_id' => 'Una billetera no puede tener una cuenta bancaria vinculada.',
                ]);
            }
        }

        if (! in_array($this->type, [self::TYPE_BANK, self::TYPE_WALLET], true)) {
            throw ValidationException::withMessages([
                'type' => 'Tipo de cuenta inválido.',
            ]);
        }
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function companyBilletera(): BelongsTo
    {
        return $this->belongsTo(CompanyBilletera::class, 'company_billetera_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TreasuryTransaction::class, 'treasury_account_id');
    }

    /**
     * Nombre visible: el label manual o el deducido de la cuenta vinculada.
     */
    public function displayName(): string
    {
        if (filled($this->label)) {
            return $this->label;
        }

        if ($this->type === self::TYPE_BANK) {
            $bank = $this->bankAccount?->bank;

            return trim(($bank?->short_name ? $bank->short_name . ' ' : '')
                . ($this->bankAccount?->description ?? $this->bankAccount?->number ?? 'Cuenta bancaria'));
        }

        $billetera = $this->companyBilletera?->billetera;

        return trim(($billetera?->short_name ? $billetera->short_name . ' ' : '')
            . ($this->companyBilletera?->account_name ?? $this->companyBilletera?->account_number ?? 'Billetera'));
    }

    /**
     * Solo cuentas activas.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Cuentas de tipo banco.
     */
    public function scopeBanks(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_BANK);
    }

    /**
     * Cuentas de tipo billetera.
     */
    public function scopeWallets(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_WALLET);
    }

    /**
     * Métodos de pago asignados a esta cuenta (para el panel de configuración).
     */
    public function paymentMethods()
    {
        return PaymentMethod::query()
            ->where('bank_account_id', $this->type === self::TYPE_BANK ? $this->bank_account_id : null)
            ->when($this->type === self::TYPE_WALLET && $this->companyBilletera, function ($query) {
                $query->orWhereIn(
                    'description',
                    [$this->companyBilletera->billetera->short_name, $this->companyBilletera->billetera->full_name]
                );
            })
            ->get();
    }
}
