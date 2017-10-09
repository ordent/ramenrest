<?php

namespace Ordent\RamenRest\Events;

// use Illuminate\Queue\SerializesModels;
use Illuminate\Http\UploadedFile;

class FileHandlerEvent
{
    // use SerializesModels;

    public $file;
    public $key;
    public $type;
    public $input;
    /**
     * Create a new event instance.
     *
     * @param  Order  $order
     * @return void
     */
    public function __construct(UploadedFile $file, $key, $input)
    {
        $this->file  = $file;
        $this->key   = $key;
        $this->input = $input;
        $this->type  = $file->getMimeType();
    }


}