<?php
namespace App\Helpers;


class HEncoding {
    static function detect_encoding ($text)
    {
        if(mb_detect_encoding($text, null, true) == 'UTF-8')
        {
            return 'UTF-8';
        }
        else
        {
            $encodings = array (
                'Windows-1251' => array(
                    238 => 0.095249209893009,
                    229 => 0.06836817536026,
                    224 => 0.067481298384992,
                    232 => 0.055995027400041,
                    237 => 0.052242744063325,
                    242 => 0.048259227579808,
                    241 => 0.044373930818522,
                    235 => 0.041607469048102,
                    226 => 0.037182869900548,
                    240 => 0.036257574878947,
                    234 => 0.027272101249674,
                    228 => 0.024573052277538,
                    236 => 0.023654281075125,
                    243 => 0.022505001594711,
                    239 => 0.020093363101279,
                    255 => 0.017963698570559,
                    252 => 0.016236698657543,
                    251 => 0.016152251442489,
                    227 => 0.016038809475485,
                    231 => 0.014150888689147,
                    225 => 0.01362825828525,
                    247 => 0.011749760793296,
                    233 => 0.0097143292064136,
                    230 => 0.0087973701759981,
                    248 => 0.0076538896459741,
                    245 => 0.0070917538925454,
                    254 => 0.00510996259677,
                    246 => 0.0029893589260344,
                    249 => 0.0024649163501406,
                    253 => 0.002252892226507,
                    205 => 0.0021318391371162,
                    207 => 0.0018574762967903,
                    244 => 0.0015961610948418,
                    194 => 0.0014044332975731,
                    206 => 0.0013188987793209,
                    192 => 0.0012623590130186,
                    202 => 0.0011804488387602,
                    204 => 0.001061932790165,
                ),
                'CP866' => array(
                    174 => 0.095249209893009,
                    165 => 0.06836817536026,
                    160 => 0.067481298384992,
                    168 => 0.055995027400041,
                    173 => 0.052242744063325,
                    226 => 0.048259227579808,
                    225 => 0.044373930818522,
                    171 => 0.041607469048102,
                    162 => 0.037182869900548,
                    224 => 0.036257574878947,
                    170 => 0.027272101249674,
                    164 => 0.024573052277538,
                    172 => 0.023654281075125,
                    227 => 0.022505001594711,
                    175 => 0.020093363101279,
                    239 => 0.017963698570559,
                    236 => 0.016236698657543,
                    235 => 0.016152251442489,
                    163 => 0.016038809475485,
                    167 => 0.014150888689147,
                    161 => 0.01362825828525,
                    231 => 0.011749760793296,
                    169 => 0.0097143292064136,
                    166 => 0.0087973701759981,
                    232 => 0.0076538896459741,
                    229 => 0.0070917538925454,
                    238 => 0.00510996259677,
                    230 => 0.0029893589260344,
                    233 => 0.0024649163501406,
                    237 => 0.002252892226507,
                    141 => 0.0021318391371162,
                    143 => 0.0018574762967903,
                    228 => 0.0015961610948418,
                    130 => 0.0014044332975731,
                    142 => 0.0013188987793209,
                    128 => 0.0012623590130186,
                    138 => 0.0011804488387602,
                    140 => 0.001061932790165,
                )
            );
            $enc_rates = array(
                'Windows-1251' => 0,
                'CP866' => 0
            );
            for($i = 0; $i < strlen($text); $i++)
            {
                foreach ($encodings as $encoding => $char_specter)
                {
                    if (array_key_exists(ord($text[$i]), $char_specter))
                        $enc_rates[$encoding] += $char_specter[ord($text[$i])];
                }
            }
            return array_search(max($enc_rates), $enc_rates);
        }
    }
}