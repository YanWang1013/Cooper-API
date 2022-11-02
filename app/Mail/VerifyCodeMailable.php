<?php

namespace App\Mail;

use App\Http\Models\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyCodeMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        //

        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        //$address = Settings::where('key', 'contact_email')->first();
        //$name = Settings::where('key', 'site_title')->first();
        return $this->view($this->data['view_name'])->with($this->data);
        //return $this->from($address->value, $name->value)->$this->view('email')->with($this->data);
    }
}
