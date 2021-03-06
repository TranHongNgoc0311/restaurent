<?php

namespace App\Http\Controllers\Admin;

use PDF;
use App\Models\Order;
use App\Models\Customer;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $order = Order::orderBy('status')->paginate(10);
        return view('Backend.order.index',compact('order'));
    }

    public function TotalPrice($order)
    {
        $result = 0;
        $service_price = $order->service->price * $order->service->sale / 100;
        $type_price = ($order->service_type == 1)? '15000':'';
        foreach ($order->details as $details) {
            $product_price = $details->menu->price - $details->menu->price * $details->menu->sale / 100;
            $result += $product_price * $order->table_count;
        }
        return $result + $service_price + $type_price;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $order = Order::find($id);
        if ($request->status == 5 && $order->status != 5 && !empty($order->customer_id)) {
            $TotalPrice = $this->TotalPrice($order);
            $point = $TotalPrice/100000;
            Customer::find($order->customer_id)->update(['point' => $point]);
        }
        $order->update(['status' => $request->status]);
        return back()->with('success','???? c???p nh???t tr???ng th??i ????n h??ng m??: '.$id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        OrderDetail::where('order_id',$id)->delete();
        Order::find($id)->delete();
        return back()->with('success','???? x??a ????n h??ng m??: '.$id);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadHTML($this->convert_customer_data_to_html($id));
        return $pdf->stream();
    }

    function convert_customer_data_to_html($id)
    {
     $order_info = Order::find($id);
     if($order_info->status == 1){
        $status = 'Cho duyet';
    }elseif($order_info->status == 2){
        $status = 'Da duyet';
    }elseif($order_info->status == 3){
        $status = 'Cho thanh toan';
    }elseif($order_info->status == 4){
        $status = 'Huy';
    }else{
        $status = 'Hoan tat';
    } 

    if($order_info->type == 1){
        $type = 'Tai Food - Kingdom';
    }else{
        $type = 'Tai gia';
    }                          
    $output = '
    <h3 align="center">Don Hang Ma '.$order_info->id.'</h3>
    <table width="100%" style="border-collapse: collapse; border: 0px;">
    <tr style="background-color:#777;">
    <th style="border: 1px solid; padding:12px;" width="20%">Ma ID</th>
    <td style="border: 1px solid; padding:12px;">'.$order_info->id.'</td>
    </tr>
    <tr>
    <th style="border: 1px solid; padding:12px;" width="30%">Khach hang</th>
    <td style="border: 1px solid; padding:12px;">
    '.$this->convert_vi_to_en($order_info->first_name.' '.$order_info->last_name).'
    </td>
    </tr>
    <tr>
    <th style="border: 1px solid; padding:12px;" width="15%">Dich vu</th>
    <td style="border: 1px solid; padding:12px;">'.$this->convert_vi_to_en($order_info->service->name).'</td>
    </tr>
    <tr>
    <th style="border: 1px solid; padding:12px;" width="15%">Loai dich vu</th>
    <td style="border: 1px solid; padding:12px;">'.$type.'</td>
    </tr>
    <tr>
    <th style="border: 1px solid; padding:12px;" width="15%">Trang thai</th>
    <td style="border: 1px solid; padding:12px;">'.$status.'</td>
    </tr>
    <tr>
    <th style="border: 1px solid; padding:12px;" width="15%">So ban (suat)</th>
    <td style="border: 1px solid; padding:12px;">'.$order_info->table_count.'</td>
    </tr>
    <tr>
    <th style="border: 1px solid; padding:12px;" width="15%">Dia chi</th>
    <td style="border: 1px solid; padding:12px;">'.$order_info->address.'</td>
    </tr>
    <tr>
    <th style="border: 1px solid; padding:12px;" width="20%">Don gia</th>
    <td style="border: 1px solid; padding:12px;">'.number_format($this->TotalPrice($order_info),2,",",".").' VND</td>
    </tr>
    </table>
    '; 
    return $output;
}

public function convert_vi_to_en($str) {
    $str = preg_replace("/(??|??|???|???|??|??|???|???|???|???|???|??|???|???|???|???|???)/", 'a', $str);
    $str = preg_replace("/(??|??|???|???|???|??|???|???|???|???|???)/", 'e', $str);
    $str = preg_replace("/(??|??|???|???|??)/", 'i', $str);
    $str = preg_replace("/(??|??|???|???|??|??|???|???|???|???|???|??|???|???|???|???|???)/", 'o', $str);
    $str = preg_replace("/(??|??|???|???|??|??|???|???|???|???|???)/", 'u', $str);
    $str = preg_replace("/(???|??|???|???|???)/", 'y', $str);
    $str = preg_replace("/(??)/", 'd', $str);
    $str = preg_replace("/(??|??|???|???|??|??|???|???|???|???|???|??|???|???|???|???|???)/", 'A', $str);
    $str = preg_replace("/(??|??|???|???|???|??|???|???|???|???|???)/", 'E', $str);
    $str = preg_replace("/(??|??|???|???|??)/", 'I', $str);
    $str = preg_replace("/(??|??|???|???|??|??|???|???|???|???|???|??|???|???|???|???|???)/", 'O', $str);
    $str = preg_replace("/(??|??|???|???|??|??|???|???|???|???|???)/", 'U', $str);
    $str = preg_replace("/(???|??|???|???|???)/", 'Y', $str);
    $str = preg_replace("/(??)/", 'D', $str);
  //$str = str_replace(" ", "-", str_replace("&*#39;","",$str));
    return $str;
}
}
