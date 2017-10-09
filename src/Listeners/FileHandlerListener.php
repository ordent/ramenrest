<?php

namespace Ordent\RamenRest\Listeners;

use Ordent\RamenRest\Events\FileHandlerEvent;

class FileHandlerListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  OrderShipped  $event
     * @return void
     */
    public function handle(FileHandlerEvent $event)
    {
        // if type is image
        if(substr($event->type, 0, 5) == "image"){
          return $this->handleImage($event);
        }else{
          return $event->file;
        }
    }

    private function handleImage(FileHandlerEvent $event){
      
      // for now return the image back
      return $event->file;
    }
}