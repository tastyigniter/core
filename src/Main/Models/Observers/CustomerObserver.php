<?php

namespace Igniter\Main\Models\Observers;

use Igniter\Main\Models\Customer;

class CustomerObserver
{
    public function created(Customer $customer)
    {
        $customer->saveCustomerGuestOrder();
    }

    public function saved(Customer $customer)
    {
        $customer->restorePurgedValues();

        if (!$customer->exists) {
            return;
        }

        if ($customer->status && !$customer->is_activated) {
            $customer->completeActivation($customer->getActivationCode());
        }

        if (array_key_exists('addresses', $customer->getAttributes())) {
            $customer->saveAddresses(array_get($customer->getAttributes(), 'addresses', []));
        }
    }
}
