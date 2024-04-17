<?php
namespace App\Traits\Attributes;

use App\Modules\Sections\Section;
use App\Modules\Commissions\Commission;
use App\Modules\Directions\Direction;
use App\Modules\Directions\DirectionTypes\DirectionType;

trait GetLink {


    function setExternalLinkAndEncode($value) {
        if (empty($value) || !is_array($value) || !isset($value['type']) || !isset($value['link'])) {
            return json_encode($value);
        }
        if ($value['type'] == "external" && !preg_match("/^http(s)?:\/\//", $value['link'])) {
            $value['link'] = "http://" . $value['link'];
        }
        return json_encode($value);
    }

    /**
     * Mutators
     */
    public function setRedirectAttribute($value) {
        $this->attributes['redirect'] = $this->setExternalLinkAndEncode($value);
    }

    public function getLinkAttribute() {
        if(empty($this->attributes['redirect'])) return null;
        
        $redirect = (object) json_decode($this->attributes['redirect'], true);
        
        if(!isset($redirect->type) || !isset($redirect->link)) return null;

        switch ($redirect->type) {
            case 'section':
                $section = Section::find($redirect->link)?->append('path');
                return $section?->path ?? null;
                break;
            case 'direction':
                $direction = Direction::with('type')->find($redirect->link);
                return $direction?->slug && $direction?->type?->code ? "/directions/{$direction?->type?->code}/{$direction?->slug}" : null;
                break;
            case 'direction_type':
                $direction_type = DirectionType::find($redirect->link);
                return $direction_type?->code ? "/directions/{$direction_type?->code}" : null;
                break;
            case 'commission':
                $component = 'Commissions';
                $commission = Commission::find($redirect->link);
                $path = Section::thisSiteFront()->component($component)->first()?->path;
                return $path && $commission?->slug ? "$path/{$commission?->slug}".'.htm' : null;
                break;
            default:
                return $redirect->link;
        }
    }

}