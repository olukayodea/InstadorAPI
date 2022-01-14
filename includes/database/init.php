<?php
class init extends database {
    public function createTables() {
        global $usersCourier;
        global $usersCustomers;
        global $usersSiteAdmin;
        global $usersStoreAdmin;
        global $userStoreRoles;
        global $userSiteRoles;
        global $tax;
        global $options;
        global $media;
        global $store;
        global $storeList;
        global $category;
        global $inventory;
        global $orders;
        global $cart;
        global $transactions;
        global $cards;
        global $bankAccount;
        global $tickets;
        global $wallet;

        $usersCourier->initialize_table();
        $usersCustomers->initialize_table();
        $userSiteRoles->initialize_table();
        $usersSiteAdmin->initialize_table();
        $usersStoreAdmin->initialize_table();
        $userStoreRoles->initialize_table();
        $tax->initialize_table();
        $options->initialize_table();
        $media->initialize_table();
        $store->initialize_table();
        $storeList->initialize_table();
        $category->initialize_table();
        $inventory->initialize_table();
        $orders->initialize_table();
        $orders->initialize_data_table();
        $orders->initialize_location_table();
        $cart->initialize_table();
        $transactions->initialize_table();
        $cards->initialize_table();
        $bankAccount->initialize_table();
        $tickets->initialize_table();
        $wallet->initialize_table();
        $wallet->initialize_request_table();
        $wallet->initialize_advice_table();

        // $this->query("UPDATE `wallet` SET `batch_id` = NULL, `fulfil_store` = 0, `fulfil_courier` = 0");
        // $this->query("DELETE FROM `wallet` WHERE `total` < 0");

        // $wallet->clear_request_table();
        // $wallet->clear_advice_table();
    }

    public function refresh() {
        global $usersCourier;
        global $usersCustomers;
        global $usersSiteAdmin;
        global $usersStoreAdmin;
        global $userStoreRoles;
        global $userSiteRoles;
        global $tax;
        global $options;
        global $media;
        global $store;
        global $storeList;
        global $category;
        global $inventory;
        global $orders;
        global $cart;
        global $transactions;
        global $cards;
        global $bankAccount;
        global $tickets;
        global $wallet;

        // $usersCourier->clear_table();
        // $usersCustomers->clear_table();
        // $usersSiteAdmin->clear_table();
        // $usersStoreAdmin->clear_table();
        // $userStoreRoles->clear_table();
        // $userSiteRoles->clear_table();
        // $tax->clear_table();
        // $options->clear_table();
        // $media->clear_table();
        // $store->clear_table();
        // $storeList->clear_table();
        // $category->clear_table();
        // $inventory->clear_table();
        // $orders->clear_table();
        // $orders->clear_data_table();
        // $orders->clear_location_table();
        // $cart->clear_table();
        // $transactions->clear_table();
        // $cards->clear_table();
        // $bankAccount->clear_table();
        // $tickets->clear_table();
        // $wallet->clear_table();
        $wallet->clear_request_table();
        $wallet->clear_advice_table();
    }

    public function remove() {
        global $usersCourier;
        global $usersCustomers;
        global $usersSiteAdmin;
        global $usersStoreAdmin;
        global $userStoreRoles;
        global $userSiteRoles;
        global $tax;
        global $options;
        global $media;
        global $store;
        global $storeList;
        global $category;
        global $inventory;
        global $orders;
        global $cart;
        global $transactions;
        global $cards;
        global $bankAccount;
        global $tickets;
        global $wallet;
        
        // $usersCourier->delete_table();
        // $usersCustomers->delete_table();
        // $usersSiteAdmin->delete_table();
        // $usersStoreAdmin->delete_table();
        // $userStoreRoles->delete_table();
        // $userSiteRoles->delete_table();
        // $tax->delete_table();
        $options->delete_table();
        // $media->delete_table();
        // $store->delete_table();
        // $storeList->delete_table();
        // $category->delete_table();
        // $inventory->delete_table();
        // $orders->delete_table();
        // $orders->delete_data_table();
        // $orders->delete_location_table();
        // $cart->delete_table();
        // $transactions->delete_table();
        // $cards->delete_table();
        // $bankAccount->delete_table();
        // $tickets->delete_table();
        // $wallet->delete_table();
        $wallet->delete_request_table();
        $wallet->delete_advice_table();
    }
}
?>