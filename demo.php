<?php

function numberOfCoins($amount) {
    $coins = array(2, 1, 0.5, 0.2, 0.1, 0.05, 0.02, 0.01);

    print_r($coins);


    $run = count($coins);
    $count = 0;
    $total = 0;
    while ($run > 0) {
        if ($amount > $coins[$count]) {

             $amt = $amount/$coins[$count];
            $split = explode(".", $amt);
             $total = $total+$split[0];

            $amount = $split[1];
        }
        $run--;
    }

    return $total;

}
echo numberOfCoins(15);
?>