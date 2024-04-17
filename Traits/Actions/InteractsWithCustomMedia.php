<?php

namespace App\Traits\Actions;

use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\FileAdder;

trait InteractsWithCustomMedia {
  
  use InteractsWithMedia {
    InteractsWithMedia::addMedia as parentAddMedia;
  }

  public function addMediaSubName($file): FileAdder {
    // dd($file);
    $name = $file->getClientOriginalName();
    $ext  = $file->extension();

    if(mb_strlen($name) > 130) {
      $new_name = mb_substr($name, 0, 130);
      return $this->parentAddMedia($file)->usingFileName($new_name.'.'.$ext);
    } else {
      return $this->parentAddMedia($file);
    }
  }
}