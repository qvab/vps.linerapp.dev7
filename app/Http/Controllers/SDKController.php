<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Link;

class SDKController extends Controller {
    public function index(Request $request, $products=null)
    {
    	if(is_null($products)) {
    		return Product::all();
    	}
    	else {
            $data = [];
    		$links = Link::where([
                'entity_id' => $request->input('entity_id'),
                'entity' => $request->input('type')
            ])
            ->get();

            foreach ($links as $link) {
                $data[] = [
                    'id' => $link->product_id,
                    'name' => $link->product->name,
                    'price' => $link->product->price,
                    'sku' => $link->product->sku,
                    'quantity' => $link->quantity
                ];
            }

            return $data;
    	}
    }

    public function link(Request $request)
    {
    	if($request->input('link')) {
            foreach ($request->input('link') as $link) {
                Link::updateOrCreate(
                    [
                        'entity' => $link['from'],
                        'entity_id' => $link['from_id'],
                        'product_id' => $link['to_id']
                    ],
                    [
                        'quantity' => $link['quantity']
                    ]
                );

                $json_res['query'][] = $link['from'] . '|' . $link['from_id'] . '|' . $link['quantity'] . '|' . $link['to_id'];
            }
        }

        if($request->input('unlink')) {
        	foreach ($request->input('unlink') as $link) {
        		Link::where([
        			'entity' => $link['from'],
        			'entity_id' => $link['from_id'],
        			'product_id' => $link['to_id'],
        		])->delete();

        		$json_res['query'][] = $link['from'] . '|' . $link['from_id'] . '|' . $link['to_id'];
        	}
        }

    	return $json_res;
    }

    public function search(Request $request)
    {
    	return Product::where(
    		'name', 'LIKE', '%' . $request->input('query') . '%'
    	)->get();
    }
}
