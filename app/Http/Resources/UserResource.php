<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $shop = $this->currentShop();
        $role = $shop && $shop->owner_id === $this->id
            ? 'owner'
            : ($shop ? $this->memberships()->where('shop_id', $shop->id)->value('role') : null);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->firstName(),
            'initials' => $this->initials(),
            'email' => $this->email,
            'phone' => $this->phone,
            'locale' => $this->locale,
            'role' => $role,
            'role_label' => $role ? ucfirst($role) : null,
        ];
    }
}
