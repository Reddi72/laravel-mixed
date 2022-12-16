<?php

namespace App\Traits;

use App\Models\AppSettings;
use Cache;
use Carbon\Carbon;

trait AppSettingsTrait {

    // get setting value
    public function getSetting($name)
    {
        $settings = $this->getCache();
        $value = array_get($settings, $name);
        return ($value !== '') ? $value : NULL;
    }

    // create-update setting
    public function setSetting($name, $value)
    {
        $this->storeSetting($name, $value);
        $this->setCache();
    }

    // create-update multiple settings at once
    public function setSettings($data = [])
    {
        foreach($data as $name => $value)
        {
            $this->storeSetting($name, $value);
        }
        $this->setCache();
    }

    private function storeSetting($name, $value)
    {
        $record = AppSettings::where(['user_id' => $this->id, 'name' => $name])->first();
        if($record)
        {
            $record->value = $value;
            $record->save();
        } else {
            $data = new AppSettings(['name' => $name, 'value' => $value]);
            $this->settings()->save($data);
        }
    }

    private function getCache()
    {
        if (Cache::has('app_settings_' . $this->id))
        {
            return Cache::get('app_settings_' . $this->id);
        }
        return $this->setCache();
    }

    private function setCache()
    {
        if (Cache::has('app_settings_' . $this->id))
        {
            Cache::forget('app_settings_' . $this->id);
        }
        $settings = $this->settings->lists('value','name');
        Cache::forever('app_settings_' . $this->id, $settings);
        return $this->getCache();
    }

}