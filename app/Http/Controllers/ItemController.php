<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ItemController extends Controller
{

    protected string $url = 'https://api.mercadolibre.com/items';

    protected array $items = [];

    /**
     * Coupons function, returns max combo of items by given budget / amount
     *
     * This function calls maxCombo to know the max combo of items, then
     *
     * @bodyParam item_ids array of ids
     * @bodyParam amount budget amount for the purchase / coupon
     * @return mixed
     */
    public function coupons(){
        // Laravel's request helper to validate proper attributes in order to call this API endpoint.
        request()->validate([
            'item_ids' => ['required','array'],
            'item_ids.*' => ['string'],
            'amount' => ['required','integer','gt:0']
        ]);

        // Call max combo function to determine best combination of items
        $this->maxCombo(request()->item_ids,request()->amount);

        return $this->items[0];
    }

    /**
     * Favorite items
     * Returns top 5 items by sold quantity, quantity must be over 0
     *
     * @param int $length
     * @return mixed
     */
    public function topFavorites(int $length = 5){
        return Item::select('id','quantity')->where('quantity','>',0)->orderBy('quantity','ASC')->limit($length)->get();
    }

    /**
     * Max Combo
     *
     * Calls the Mercadolibre API to consult item price based on the item ids provided
     *
     * @param array $item_ids
     * @param int $budget
     * @return void
     */
    public function maxCombo(array $item_ids, int $budget)
    {
        // Buffer array for response of items
        $items = [];

        // Fetch items from Mercadolibre's API
        $response = Http::get($this->url,[
            // turn array of Ids into string to use the follow url
            // https://api.mercadolibre.com/items?ids=id1,id2
            'ids' => implode(',',$item_ids)
        ]);

        // Parse result into a more readable array ['id','price','sold_quantity']
        for($i = 0; $i < count($response->json()); $i++){
            $item = Item::updateOrCreate([
                'id' => $response[$i]['body']['id'],
                'price' => $response[$i]['body']['price'],
                'quantity' => $response[$i]['body']['sold_quantity']
            ]);
            $items[] = $item->toArray();
        }

        // Use Depth First Search to calculate the max combo
        $this->dfs($items,0,$budget,0,[]);

        // Sort the combos by total value, the highest value on top.
        usort($this->items, fn($a,$b) => $a['total'] < $b['total'] ? 1 : -1);
    }

    /**
     * @param array $candidates
     * @param float $sum
     * @param float $target
     * @param int $index
     * @param array $result
     * @return void
     */
    public function dfs(array $candidates, float $sum, float $target, int $index, array $result)
    {
        if($sum > $target){
            //if sum is bigger than, return;
            return;
        }elseif($sum < $target){
            //Check if another item can be added to the result

            //If result is not empty, add it to the global items list.
            if(count($result) > 0){
                $this->items[] = ['total'=>$sum,'item_ids'=>array_column($result,'id')];
            }

            // iterate candidates
            for($i = $index; $i<count($candidates); $i++){

                //Validate that no item gets duplicated
                if(!in_array($candidates[$i],$result)) {

                    // If current sum + candidate price is within target / budget, add it to the array.
                    if($sum+$candidates[$i]['price'] <= $target){

                        $result[] = $candidates[$i];
                        $this->dfs($candidates,($sum+$candidates[$i]['price']),$target,$i,$result);
                        array_pop($result);
                    }
                }
            }
        }
    }
}
