<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\User;
use App\TicketType;
use App\Ticket;
use App\Invoice;
use App\EventAnswer;
use App\Address;
use GuzzleHttp\Client;
use Mail;
use App\Mail\TicketPurchase as vMail;

class PaymentsController extends Controller
{   
    public function sessionId (Request $request, $id)
    {
        if (auth()->user() == null) {
            flash('Please login to purchase tickets')->error();

            return redirect('/login');
        }

        $type = TicketType::where('id', $id)->first();
        $appURl = config('app.url');

        $store_id = env('SSL_STORE_ID', false);
        $store_pass =  env('SSL_STORE_PASS', false);
        $total_amount = $type->price;
        $total_amount = number_format($total_amount, 2, '.', '');
        $currency = 'BDT';
        $tran_id = new Carbon;
        $tran_id = $tran_id->format('Y-m-d::H:i:s.u');
        $tran_id = $tran_id.auth()->user()->id.$type->event_id;
        $success_url = $appURl.'/api/payment/0/'.$type->event_id.'/'.auth()->user()->id.'/'.$id;
        $fail_url = $appURl.'/api/payment/1/'.$type->event_id.'/'.auth()->user()->id.'/'.$id;
        $cancel_url = $appURl.'/api/payment/2/'.$type->event_id.'/'.auth()->user()->id.'/'.$id;
        $emi_potion = '0';
        $cus_name = auth()->user()->profile->f_name.auth()->user()->profile->m_name.auth()->user()->profile->l_name;
        $cus_email = auth()->user()->email;
        $cus_phone = auth()->user()->profile->phone;
        $client = new Client();

        $response = $client->request('POST', 'https://sandbox.sslcommerz.com/gwprocess/v3/api.php', [
            'form_params' => [
                'store_id' => $store_id,
                'store_passwd' => $store_pass,
                'total_amount' => $total_amount,
                'currency' => $currency,
                'tran_id' => $tran_id,
                'success_url' => $success_url,
                'fail_url' => $fail_url,
                'cancel_url' => $cancel_url,
                'emi_potion' => $emi_potion,
                'cus_name' => $cus_name,
                'cus_email' => $cus_email,
                'cus_phone' => $cus_phone,
            ]
        ]);

        if (json_decode($response->getBody())->status == 'FAILED') {
            $url = '/events/'.$type->event_id;

            flash(json_decode($response->getBody())->failedreason)->error();

            return redirect($url);
        }

        return redirect(json_decode($response->getBody())->redirectGatewayURL);
    }

    public function status (Request $request, $status, $id, $user, $type)
    {
        if ($status == 0 && $request->status == 'VALID') {
            $now = new Carbon;
            $now = $now->format('Ymd');
            $total_tickets = count(Ticket::where('event_id', $id)->get());
            $unsold_tickets = count(Ticket::where('event_id', $id)->whereNull('user_id')->get());
            $barcode = $now.time().$id;

            $invoice = new Invoice;
            $invoice->number = ($total_tickets - $unsold_tickets + 1);
            $invoice->barcode = '1 '.$barcode;
            $invoice->save();

            $ticket = Ticket::where('event_id', $id)->where('ticket_type_id', $type)->whereNull('user_id')->first();
            $ticket->user_id = $user;
            $ticket->invoice_id = $invoice->id;
            $ticket->save();
            $user = User::find($user);

            $invoice->barcode = '1 '.$id.' '.$ticket->id;
            $invoice->save();
            Mail::to($user->email)->queue(new vMail($user, $ticket));

            return view('transfer-status.transfer-success');
        } else {
            $ticket = Ticket::where('ticket_type_id', $id)->where('user_id', $user)->first();
            $answers = EventAnswer::where('event_id', $id)->where('user_id', $user)->get();

            foreach ($answers as $answer) {
                $answer->delete();
            }

            $url = '/events/'.$id;

            flash('Something went wrong')->error();

            return view('transfer-status.transfer-failure');
        }
    }

    public function address (Request $request, $id)
    {
        return view('tickets/address')->with('id', $id);
    }

    public function store (Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'street' => 'required',
            'division' => 'division',
        ]);

        $address = new Address;
        $address->invoice_id = $request->id;
        $address->address = $request->street;
        $address->division = $request->division;
        $address->save();

        return redirect('/tickets');
    }
}