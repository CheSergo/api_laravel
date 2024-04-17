<?php

namespace App\Helpers\Data;

use App\Helpers\HEncoding;
use Maatwebsite\Excel\Facades\Excel;
use App\Import\ImportFile;
use App\Models\Sites\Data\OpenData;
use DOMDocument;

class HConverter {

    /**
     * @param $file_path
     * @return array
     */
    static function csv_to_standart($file_path) {
        $content = file_get_contents($file_path);
        $from_encoding = HEncoding::detect_encoding($content);
        $row_delimiter = "\r\n";
        if( strpos($content, "\r\n") === false )
            $row_delimiter = "\n";
        $rows = explode( $row_delimiter, $content );
        $rows = array_diff($rows, array(''));
        $col_delimiter='';
        foreach( $rows as $i => $row ){
            if( strpos( $row, ',') === false)
            {
                if( strpos( $row, ';') === false) $col_delimiter = "\t";
                if( strpos( $row, "\t") === false) $col_delimiter = ';';
            }
            if( strpos( $row, ';') === false)
            {
                if( strpos( $row, ',') === false) $col_delimiter = "\t";
                if( strpos( $row, "\t") === false) $col_delimiter = ',';
            }
            if( strpos( $row, "\t") === false)
            {
                if( strpos( $row, ',') === false) $col_delimiter = ';';
                if( strpos( $row, ';') === false) $col_delimiter = ',';
            }
            if( $col_delimiter ) break;
        }
        if( ! $col_delimiter ){
            $delim_counts = array( ';'=>array(), ','=>array(), "\t" => array() );
            foreach( $rows as $i => $row ){
                $delim_counts[','][$i] = substr_count( $row, ',' );
                $delim_counts[';'][$i] = substr_count( $row, ';' );
                $delim_counts["\t"][$i] = substr_count( $row, "\t" );
            }
            $delim_counts = array_map( 'array_filter', $delim_counts );
            $delim_counts = array_map( 'array_count_values', $delim_counts );
            $delim_counts = array_filter($delim_counts);
            $delim_counts = array_map( 'max', $delim_counts );
            $col_delimiter = array_search( max($delim_counts), $delim_counts );
        }
        $rows_del = [];
        if($from_encoding == 'UTF-8')
        {
            foreach ($rows as $row)
            {
                $data = str_getcsv($row, $col_delimiter);
                $data_del = [];
                foreach ($data as $item)
                {
                    $item = preg_replace_callback(
                        '#(([\"]{2,})|(?![^\W])(\"))|([^\s][\"]+(?![\w]))#u',
                        function ($matches) {
                            if (count($matches)===3) return "«»";
                            else if ($matches[1]) return str_replace('"',"«",$matches[1]);
                            else return str_replace('"',"»",$matches[4]);
                        },
                        str_replace("&quot;", '"', str_replace("'", '"', $item))
                    );
                    $item = str_replace("\n", "", $item);
                    $item = str_replace("&lt;", "!", $item);
                    array_push($data_del, $item);
                }
                array_push($rows_del, $data_del);
            }
        }
        else
        {
            foreach ($rows as $row)
            {
                $data = str_getcsv($row, $col_delimiter);
                $data = mb_convert_encoding($data, 'UTF-8', $from_encoding);
                $data_del = [];
                foreach ($data as $item)
                {
                    $item = preg_replace_callback(
                        '#(([\"]{2,})|(?![^\W])(\"))|([^\s][\"]+(?![\w]))#u',
                        function ($matches) {
                            if (count($matches)===3) return "«»";
                            else if ($matches[1]) return str_replace('"',"«",$matches[1]);
                            else return str_replace('"',"»",$matches[4]);
                        },
                        str_replace("&quot;", '"', str_replace("'", '"', $item))
                    );
                    $item = str_replace("\n", "", $item);
                    $item = str_replace("&lt;", "!", $item);
                    array_push($data_del, $item);
                }
                array_push($rows_del, $data_del);
            }
        }
        return $rows_del;
    }

    /**
     * @param $file_path
     * @return array
     */
    static function csv($file_path)
    {
        $rows = Excel::toArray(new ImportFile, $file_path);
        $rows_del=[];
        foreach ($rows[0] as $row)
        {
            $row_del=[];
            foreach ($row as $item)
            {
                $item = str_replace("\n", "", $item);
                $item = preg_replace_callback(
                    '#(([\"]{2,})|(?![^\W])(\"))|([^\s][\"]+(?![\w]))#u',
                    function ($matches) {
                        if (count($matches)===3) return "«»";
                        else if ($matches[1]) return str_replace('"',"«",$matches[1]);
                        else return str_replace('"',"»",$matches[4]);
                    },
                    str_replace("&quot;", '"', str_replace("'", '"', $item))
                );
                $item = str_replace("\n", "", $item);
                $item = str_replace("&lt;", "!", $item);
                array_push($row_del, $item);
            }
            array_push($rows_del, $row_del);
        }
        return $rows_del;
    }


    /**
     * @param $file_path
     * @return stringstorage_path('app/opendata')
     */
    static function csv_to_standart_file($file_path)
    {
        $content = file_get_contents($file_path);
        $from_encoding = HEncoding::detect_encoding($content);
        $row_delimiter = "\r\n";
        if( strpos($content, "\r\n") === false )
            $row_delimiter = "\n";
        $rows = explode( $row_delimiter, $content );
        $rows = array_diff($rows, array(''));
        $col_delimiter='';
        foreach( $rows as $i => $row ){
            if( strpos( $row, ',') === false)
            {
                if( strpos( $row, ';') === false) $col_delimiter = "\t";
                if( strpos( $row, "\t") === false) $col_delimiter = ';';
            }
            if( strpos( $row, ';') === false)
            {
                if( strpos( $row, ',') === false) $col_delimiter = "\t";
                if( strpos( $row, "\t") === false) $col_delimiter = ',';
            }
            if( strpos( $row, "\t") === false)
            {
                if( strpos( $row, ',') === false) $col_delimiter = ';';
                if( strpos( $row, ';') === false) $col_delimiter = ',';
            }
            if( $col_delimiter ) break;
        }
        if( ! $col_delimiter ){
            $delim_counts = array( ';'=>array(), ','=>array(), "\t" => array() );
            foreach( $rows as $i => $row ){
                $delim_counts[','][$i] = substr_count( $row, ',' );
                $delim_counts[';'][$i] = substr_count( $row, ';' );
                $delim_counts["\t"][$i] = substr_count( $row, "\t" );
            }
            $delim_counts = array_map( 'array_filter', $delim_counts );
            $delim_counts = array_map( 'array_count_values', $delim_counts );
            $delim_counts = array_filter($delim_counts);
            $delim_counts = array_map( 'max', $delim_counts );
            $col_delimiter = array_search( max($delim_counts), $delim_counts );
        }
        if($from_encoding == 'UTF-8')
        {
            $path_temp_file = storage_path('app/opendata').date('Ymd\THis').'.csv';
            $temp_file = fopen($path_temp_file, 'w');
            foreach ($rows as $row)
            {
                $data = str_getcsv($row, $col_delimiter);
                fputcsv($temp_file, $data);
            }
            fclose($temp_file);
            return $path_temp_file;
        }
        else
        {
            $path_temp_file = storage_path('app/opendata').date('Ymd\THis').'.csv';
            $temp_file = fopen($path_temp_file, 'w');
            foreach ($rows as $row)
            {
                $data = str_getcsv($row, $col_delimiter);
                $data = mb_convert_encoding( $data, 'UTF-8', $from_encoding);
                fputcsv($temp_file, $data);
            }
            fclose($temp_file);
            return $path_temp_file;
        }
    }

    /**
     * @param $file_path
     * @return string
     */
    static function csv_file($file_path)
    {
        $rows = Excel::toArray(new ImportFile, $file_path);
        $path_temp_file = storage_path('app/opendata').date('Ymd\THis').'.csv';
        $temp_file = fopen($path_temp_file, 'w');
        foreach ($rows[0] as $row)
        {
            foreach ($row as $item)
            {
                if(!is_null($item))
                {
                    fputcsv($temp_file, $row);
                    break;
                }
            }
        }
        fclose($temp_file);
        return $path_temp_file;
    }

    /**
     * @param $file_path
     * @return string
     */
    static function json_file($file_path)
    {
        $rows = Excel::toArray(new ImportFile, $file_path);
        $path_temp_file = storage_path('app/opendata').date('Ymd\THis').'.json';
        $temp_file = fopen($path_temp_file, 'w');
        $data = array();
        foreach ($rows[0] as $i => $row)
        {
            if($i != 0)
            {
                foreach ($row as $j => $item)
                {
                    $data[$i - 1][$rows[0][0][$j]] = $item;
                }
            }
        }
        fwrite($temp_file, json_encode($data));
        fclose($temp_file);
        return $path_temp_file;
    }

    /**
     * @param $file_path
     * @param $file_name
     * @return string
     */
    static function xml_file($file_path, $file_name)
    {
        $rows = Excel::toArray(new ImportFile, $file_path);
        $path_temp_file = storage_path('app/opendata').date('Ymd\THis').'.json';
        $xml = new DomDocument('1.0','utf-8');
        $type = explode("-", $file_name);
        $data = $xml-> appendChild($xml -> createElement($type[0]));
        foreach ($rows[0] as $i => $row)
        {
            if($i != 0)
            {
                $line = $data -> appendChild($xml -> createElement('row'));
                foreach ($row as $j => $item)
                {
                    if(!is_null($rows[0][0][$j]))
                    {
                        $field = $line -> appendChild($xml -> createElement(str_replace(' ', '_', $rows[0][0][$j])));
                        if(!is_null($item))
                        {
                            $field -> appendChild($xml -> createTextNode($item));
                        }
                        else
                        {
                            $field -> appendChild($xml -> createTextNode('null'));
                        }
                    }
                }
            }
        }
        $xml -> formatOutput = true;
        $xml->save($path_temp_file);
        return $path_temp_file;
    }

    /**
     * @param $data
     * @param $sets
     * @return mixed
     */
    static function passport_csv ($data, $sets)
    {
        if(is_null($sets->last()->table)) {
            $file_set_last = $sets->last()->getMedia('set')->first();
            $file_path_set_last = public_path().$file_set_last->getUrl();
            $extension = (pathinfo($file_path_set_last, PATHINFO_EXTENSION) == 'xlsx' || pathinfo($file_path_set_last, PATHINFO_EXTENSION) == 'xls') ? 'csv, json, xml' : pathinfo($file_path_set_last, PATHINFO_EXTENSION);
        } else {
            $extension = 'json';
        }
        $passport = array (
            array('property', 'value'),
            array('standardversion', 'http://opendata.gosmonitor.ru/standard/3.0'),
            array('identifier', $data -> idn),
            array('title', $data->title),
            array('description', $data -> description),
            array('creator', $data->owner->title),
            array('created', date('Ymd', strtotime($sets->first()->published_at))),
            array('modified', date('Ymd', strtotime($sets->last()->published_at))),
            array('subject', $data->keywords),
            array('format', $extension),
            array('provenance', $sets->last()->last_changes),
            array('valid', $sets->last()->date_actual),
            array('publishername', $data->publisher),
            array('publisherphone', $data->phone),
            array('publishermbox', $data -> email),
        );
        $temp_file = storage_path('app/opendata').$data->id.'-'.date('Ymd\THis').'.csv';
        $file = fopen($temp_file, 'w');
        foreach ($passport as $item )
            fputcsv($file, $item);
        foreach ($sets as $set)
        {
            if(is_null($set->table)) {
                $file_set = $set->getMedia('set')->first();
                $file_path_set = public_path().$file_set->getUrl();
                $extension_set = pathinfo($file_path_set, PATHINFO_EXTENSION);
            } else {
                $extension_set = 'json';
            }
            if($extension_set == 'xlsx' || $extension_set == 'xls')
            {
                $file_name_data = 'data-'.date('Ymd\THis', strtotime($set->published_at)).'-structure-'.date('Ymd\THis', strtotime($set->published_at)).'.csv';
                $source = url('opendata', ['idn' => $data->idn, 'file_name' => $file_name_data]).'?set='.$set->id;
                fputcsv($file, array($file_name_data, $source));
                $file_name_data = 'data-'.date('Ymd\THis', strtotime($set->published_at)).'-structure-'.date('Ymd\THis', strtotime($set->published_at)).'.json';
                $source = url('opendata', ['idn' => $data->idn, 'file_name' => $file_name_data]).'?set='.$set->id;
                fputcsv($file, array($file_name_data, $source));
                $file_name_data = 'data-'.date('Ymd\THis', strtotime($set->published_at)).'-structure-'.date('Ymd\THis', strtotime($set->published_at)).'.xml';
                $source = url('opendata', ['idn' => $data->idn, 'file_name' => $file_name_data]).'?set='.$set->id;
                fputcsv($file, array($file_name_data, $source));
            }
            else
            {
                $file_name_data = 'data-'.date('Ymd\THis', strtotime($set->published_at)).'-structure-'.date('Ymd\THis', strtotime($set->published_at)).'.'.$extension_set;
                $source = url('opendata', ['idn' => $data->idn, 'file_name' => $file_name_data]).'?set='.$set->id;
                fputcsv($file, array($file_name_data, $source));
            }
        }
        foreach ($sets as $set)
        {
            if(is_null($set->table)) {
                $file_struct = $set->getMedia('structure')->first();
                $file_path_struct = public_path().$file_struct->getUrl();
                $extension_struct = pathinfo($file_path_struct, PATHINFO_EXTENSION);
            } else {
                $extension_struct = 'json';
            }
            if($extension_struct == 'xlsx' || $extension_struct == 'xls')
            {
                $file_name_data = 'structure-'.date('Ymd\THis', strtotime($set->published_at)).'.csv';
                $source = url('opendata', ['idn' => $data->idn, 'file_name' => $file_name_data]).'?set='.$set->id;
                fputcsv($file, array($file_name_data, $source));
                $file_name_data = 'structure-'.date('Ymd\THis', strtotime($set->published_at)).'.json';
                $source = url('opendata', ['idn' => $data->idn, 'file_name' => $file_name_data]).'?set='.$set->id;
                fputcsv($file, array($file_name_data, $source));
                $file_name_data = 'structure-'.date('Ymd\THis', strtotime($set->published_at)).'.xml';
                $source = url('opendata', ['idn' => $data->idn, 'file_name' => $file_name_data]).'?set='.$set->id;
                fputcsv($file, array($file_name_data, $source));
            }
            else
            {
                $file_name_data = 'structure-'.date('Ymd\THis', strtotime($set->published_at)).'.'.$extension_struct;
                $source = url('opendata', ['idn' => $data->idn, 'file_name' => $file_name_data]).'?set='.$set->id;
                fputcsv($file, array($file_name_data, $source));
            }
        }
        fclose($file);
        return response()->download($temp_file,'meta.csv')->deleteFileAfterSend(true);
    }

    /**
     * @param $data
     * @param $sets
     * @return mixed
     */
    static function passport_xml ($data, $sets)
    {
        if(is_null($sets->last()->table)) {
            $file_set_last = $sets->last()->getMedia('set')->first();
            $file_path_set_last = public_path().$file_set_last->getUrl();
            $extension = (pathinfo($file_path_set_last, PATHINFO_EXTENSION) == 'xlsx' || pathinfo($file_path_set_last, PATHINFO_EXTENSION) == 'xls') ? 'csv, json, xml' : pathinfo($file_path_set_last, PATHINFO_EXTENSION);
        } else {
            $extension = 'json';
        }
        $xml = new DomDocument('1.0','utf-8');
        $meta = $xml-> appendChild($xml -> createElement('meta'));
        $standardversion = $meta -> appendChild($xml -> createElement('standardversion'));
        $standardversion -> appendChild($xml -> createTextNode('http://OpenData.gosmonitor.ru/standard/3.0'));
        $identifier = $meta -> appendChild($xml -> createElement('identifier'));
        $identifier -> appendChild($xml -> createTextNode($data -> idn));
        $title = $meta -> appendChild($xml -> createElement('title'));
        $title -> appendChild($xml -> createTextNode($data -> title));
        $description = $meta -> appendChild($xml -> createElement('description'));
        $description -> appendChild($xml -> createTextNode($data -> description));
        $creator = $meta -> appendChild($xml -> createElement('creator'));
        $creator -> appendChild($xml -> createTextNode($data -> owner -> title));
        $created = $meta -> appendChild($xml -> createElement('created'));
        $created -> appendChild($xml -> createTextNode(date('Ymd', strtotime($sets->first()->published_at))));
        $modified = $meta -> appendChild($xml -> createElement('modified'));
        $modified -> appendChild($xml -> createTextNode(date('Ymd', strtotime($sets->last()->published_at))));
        $subject = $meta -> appendChild($xml -> createElement('subject'));
        $subject -> appendChild($xml -> createTextNode($data -> keywords));
        $format = $meta -> appendChild($xml -> createElement('format'));
        $format -> appendChild($xml -> createTextNode($extension));
        $data_tag = $meta -> appendChild($xml -> createElement('data'));
        foreach ($sets as $set)
        {
            if(is_null($set->table)) {
                $file_set = $set->getMedia('set')->first();
                $file_path_set = public_path().$file_set->getUrl();
                $extension_set = pathinfo($file_path_set, PATHINFO_EXTENSION);
            } else {
                $extension_set = 'json';
            }
            $dataversion = $data_tag -> appendChild($xml -> createElement('dataversion'));
            if($extension_set == 'xlsx' || $extension_set == 'xls')
            {
                $source = $dataversion -> appendChild($xml -> createElement('source'));
                $source_csv = $source -> appendChild($xml -> createElement('csv'));
                $file_name_data = 'data-'.date('Ymd\THis', strtotime($set->published_at)).'-structure-'.date('Ymd\THis', strtotime($set->published_at)).'.csv';
                $source_csv -> appendChild($xml -> createTextNode(url('OpenData', ['idn' => $data -> idn, 'file_name' => $file_name_data]).'?set='.$set -> id));
                $source_json = $source -> appendChild($xml -> createElement('json'));
                $file_name_data = 'data-'.date('Ymd\THis', strtotime($set->published_at)).'-structure-'.date('Ymd\THis', strtotime($set->published_at)).'.json';
                $source_json -> appendChild($xml -> createTextNode(url('OpenData', ['idn' => $data -> idn, 'file_name' => $file_name_data]).'?set='.$set -> id));
                $source_xml = $source -> appendChild($xml -> createElement('xml'));
                $file_name_data = 'data-'.date('Ymd\THis', strtotime($set->published_at)).'-structure-'.date('Ymd\THis', strtotime($set->published_at)).'.xml';
                $source_xml -> appendChild($xml -> createTextNode(url('OpenData', ['idn' => $data -> idn, 'file_name' => $file_name_data]).'?set='.$set -> id));
            }
            else
            {
                $source = $dataversion -> appendChild($xml -> createElement('source'));
                $file_name_data = 'data-'.date('Ymd\THis', strtotime($set->published_at)).'-structure-'.date('Ymd\THis', strtotime($set->published_at)).'.'.$extension_set;
                $source -> appendChild($xml -> createTextNode(url('OpenData', ['idn' => $data -> idn, 'file_name' => $file_name_data]).'?set='.$set -> id));
            }
            $created_data = $dataversion -> appendChild($xml -> createElement('created'));
            $created_data -> appendChild($xml -> createTextNode(date('Ymd\THis', strtotime($set->published_at))));
            $valid = $dataversion -> appendChild($xml -> createElement('valid'));
            if($set->date_actual != null)
                $valid -> appendChild($xml -> createTextNode(date('Ymd', strtotime($set->date_actual))));
            else
                $valid -> appendChild($xml -> createTextNode('null'));
            $structure = $dataversion -> appendChild($xml -> createElement('structure'));
            $structure -> appendChild($xml -> createTextNode(date('Ymd\THis', strtotime($set->published_at))));
        }
        $structure_tag = $meta -> appendChild($xml -> createElement('structure'));
        foreach( $sets as $set )
        {
            if(is_null($set->table)) {
                $file_struct = $set->getMedia('structure')->first();
                $file_path_struct = public_path().$file_struct->getUrl();
                $extension_struct = pathinfo($file_path_struct, PATHINFO_EXTENSION);
            } else {
                $extension_struct = 'json';
            }
            $structureversion = $structure_tag -> appendChild($xml -> createElement('structureversion'));
            if($extension_struct == 'xlsx' || $extension_struct == 'xls')
            {
                $source_structure = $structureversion -> appendChild($xml -> createElement('source'));
                $source_structure_csv = $source_structure -> appendChild($xml -> createElement('csv'));
                $file_name_structure = 'structure-' . date('Ymd\THis', strtotime($set->published_at)) . '.csv';
                $source_structure_csv -> appendChild($xml -> createTextNode(url('OpenData', ['idn' => $data -> idn, 'file_name' => $file_name_structure]).'?set='.$set -> id));
                $source_structure_json = $source_structure -> appendChild($xml -> createElement('json'));
                $file_name_structure = 'structure-' . date('Ymd\THis', strtotime($set->published_at)) . '.json';
                $source_structure_json -> appendChild($xml -> createTextNode(url('OpenData', ['idn' => $data -> idn, 'file_name' => $file_name_structure]).'?set='.$set -> id));
                $source_structure_xml = $source_structure -> appendChild($xml -> createElement('xml'));
                $file_name_structure = 'structure-' . date('Ymd\THis', strtotime($set->published_at)) . '.xml';
                $source_structure_xml -> appendChild($xml -> createTextNode(url('OpenData', ['idn' => $data -> idn, 'file_name' => $file_name_structure]).'?set='.$set -> id));
            }
            else
            {
                $source_structure = $structureversion -> appendChild($xml -> createElement('source'));
                $file_name_structure = 'structure-' . date('Ymd\THis', strtotime($set->published_at)) . '.'.$extension_struct;
                $source_structure -> appendChild($xml -> createTextNode(url('OpenData', ['idn' => $data -> idn, 'file_name' => $file_name_structure]).'?set='.$set -> id));
            }
            $created_structure = $structureversion -> appendChild($xml -> createElement('created'));
            $created_structure -> appendChild($xml -> createTextNode(date('Ymd\THis', strtotime($set->published_at))));
        }
        $publisher = $meta -> appendChild($xml -> createElement('publisher'));
        $name = $publisher -> appendChild($xml -> createElement('name'));
        $name -> appendChild($xml -> createTextNode($data -> publisher));
        $phone = $publisher -> appendChild($xml -> createElement('phone'));
        $phone -> appendChild($xml -> createTextNode($data -> phone));
        $mbox = $publisher -> appendChild($xml -> createElement('mbox'));
        $mbox -> appendChild($xml -> createTextNode($data -> email));

        $xml -> formatOutput = true;
        $temp_file = storage_path('app/opendata').$data->id.'-'.date('Ymd\THis').'.xml';
        $xml->save($temp_file);
        return response()->download($temp_file,'meta.xml')->deleteFileAfterSend(true);
    }

    /**
     * @param $data
     * @param $sets
     * @return mixed
     */
    static function passport_json ($data, $sets)
    {
        $passport = array(
            'standardversion' => 'http://opendata.gosmonitor.ru/standard/3.0',
            'identifier' => $data -> idn,
            'title' => $data -> title,
            'description' => $data -> description,
            'creator' => $data -> owner -> title,
            'created' => $sets->first()->published_at,
            'modified' => $sets->last()->published_at,
            'subject' => $data -> keywords,
            'data' => [

            ],
            'structure' => [

            ],
            'publisher' => [
                'name' => $data -> publisher,
                'phone' => $data -> phone,
                'mbox' => $data -> email
            ]
        );
        foreach ($sets as $i => $set)
        {
            $file_set = $set->getMedia('set')->first();
            if(is_null($set->table)) {
                $file_path_set = public_path().$file_set->getUrl();
                $extension_set = pathinfo($file_path_set, PATHINFO_EXTENSION);
            } else {
                $extension_set = 'json';
            }
            if($extension_set == 'xlsx' || $extension_set == 'xls')
            {
                $passport['data'][$i]['source'] = array();
                $file_name_data = 'data-'.date('Ymd\THis', strtotime($set->published_at)).'-structure-'.date('Ymd\THis', strtotime($set->published_at)).'.csv';
                $passport['data'][$i]['source']['csv'] = url('opendata', ['idn' => $data -> idn, 'file_name' => $file_name_data]).'?set='.$set -> id;
                $file_name_data = 'data-'.date('Ymd\THis', strtotime($set->published_at)).'-structure-'.date('Ymd\THis', strtotime($set->published_at)).'.json';
                $passport['data'][$i]['source']['json'] = url('opendata', ['idn' => $data -> idn, 'file_name' => $file_name_data]).'?set='.$set -> id;
                $file_name_data = 'data-'.date('Ymd\THis', strtotime($set->published_at)).'-structure-'.date('Ymd\THis', strtotime($set->published_at)).'.xml';
                $passport['data'][$i]['source']['xml'] = url('opendata', ['idn' => $data -> idn, 'file_name' => $file_name_data]).'?set='.$set -> id;
            }
            else
            {
                $file_name_data = 'data-'.date('Ymd\THis', strtotime($set->published_at)).'-structure-'.date('Ymd\THis', strtotime($set->published_at)).'.'.$extension_set;
                $passport['data'][$i]['source'] = url('opendata', ['idn' => $data -> idn, 'file_name' => $file_name_data]).'?set='.$set -> id;
            }
            $passport['data'][$i]['created'] = date('Ymd\THis', strtotime($set->published_at));
            $passport['data'][$i]['provenance'] = $set->last_changes;
            if($set->date_actual != null)
                $passport['data'][$i]['valid'] = $set->date_actual;
            else
                $passport['data'][$i]['valid'] = null;
            $passport['data'][$i]['structure'] = date('Ymd\THis', strtotime($set->published_at));
            if($extension_set == 'xlsx' || $extension_set == 'xls')
            {
                $passport['structure'][$i]['source'] = array();
                $file_name_structure = 'structure-' . date('Ymd\THis', strtotime($set->published_at)) . '.csv';
                $passport['structure'][$i]['source']['csv'] = url('opendata', ['idn' => $data -> idn, 'file_name' => $file_name_structure]).'?set='.$set -> id;
                $file_name_structure = 'structure-' . date('Ymd\THis', strtotime($set->published_at)) . '.json';
                $passport['structure'][$i]['source']['json'] = url('opendata', ['idn' => $data -> idn, 'file_name' => $file_name_structure]).'?set='.$set -> id;
                $file_name_structure = 'structure-' . date('Ymd\THis', strtotime($set->published_at)) . '.xml';
                $passport['structure'][$i]['source']['xml'] = url('opendata', ['idn' => $data -> idn, 'file_name' => $file_name_structure]).'?set='.$set -> id;
            }
            else
            {
                $file_name_structure = 'structure-' . date('Ymd\THis', strtotime($set->published_at)) . '.'.$extension_set;
                $passport['structure'][$i]['source'] = url('opendata', ['idn' => $data -> idn, 'file_name' => $file_name_structure]).'?set='.$set -> id;
            }
            $passport['structure'][$i]['created'] = date('Ymd\THis', strtotime($set->published_at));
        }

        $temp_file = storage_path('app/opendata').$data->id.'-'.date('Ymd\THis').'.json';
        $file = fopen($temp_file,'w');
        fwrite($file, json_encode($passport));
        fclose($file);
        return response()->download($temp_file,'meta.json')->deleteFileAfterSend(true);
    }

    /**
     * @return mixed
     */
    static function csv_reestr()
    {
        $data = OpenData::published()->get();
        $reestr = array(
            array('property', 'title', 'value', 'format'),
            array('standardversion', 'Версия методических рекомендаций', 'http://OpenData.gosmonitor.ru/standard/3.0'),
        );
        $temp_file = storage_path('app/opendata').'/reestr-csv-'.date('Ymd\THis').'.csv';
        $file = fopen($temp_file, 'w');
        foreach ($reestr as $item )
            fputcsv($file, $item);
        foreach ($data as $item)
        {
            if(is_null($item->set->last()->table)) {
                $file_set = $item->set->last()->getMedia('set')->first();
                $file_path_set = storage_path('app/opendata').$file_set->getUrl();
                $extension_set = pathinfo($file_path_set, PATHINFO_EXTENSION);
                if($extension_set == 'xlsx' || $extension_set == 'xls')
                    fputcsv($file, array($item->idn, $item->title, url('opendata', ['idn' => $item -> idn, 'file_name' => 'meta.csv']), 'csv'));
                else
                    fputcsv($file, array($item->idn, $item->title, url('opendata', ['idn' => $item -> idn, 'file_name' => 'meta.'.$extension_set]), $extension_set));
            } else {
                $extension_set = 'json';
                fputcsv($file, array($item->idn, $item->title, url('opendata', ['idn' => $item -> idn, 'file_name' => 'meta.'.$extension_set]), $extension_set));
            }
        }
        fclose($file);
        return response()->download($temp_file,'list.csv')->deleteFileAfterSend(true);
    }

    /**
     * @return mixed
     */
    static function xml_reestr()
    {
        $data = OpenData::published()->get();
        $xml = new DomDocument('1.0','utf-8');
        $list = $xml-> appendChild($xml -> createElement('list'));
        $standardversion = $list -> appendChild($xml -> createElement('standardversion'));
        $standardversion -> appendChild($xml -> createTextNode('http://opendata.gosmonitor.ru/standard/3.0'));
        $meta = $list -> appendChild($xml -> createElement('meta'));
        foreach ($data as $i => $item)
        {
            if(is_null($item->set->last()->table)) {
                $file_set = $item->set->last()->getMedia('set')->first();
                $file_path_set = storage_path('app/opendata').$file_set->getUrl();
                $extension_set = pathinfo($file_path_set, PATHINFO_EXTENSION);
            } else {
                $extension_set = 'json';
            }
            $item_tag = $meta -> appendChild($xml -> createElement('item'));
            $identifier = $item_tag -> appendChild($xml -> createElement('identifier'));
            $identifier -> appendChild($xml -> createTextNode($i + 1));
            $title = $item_tag -> appendChild($xml -> createElement('title'));
            $title -> appendChild($xml -> createTextNode($item->title));
            $link = $item_tag -> appendChild($xml -> createElement('link'));
            if($extension_set == 'xlsx' || $extension_set == 'xls')
            {
                $link -> appendChild($xml -> createTextNode(url('opendata', ['idn' => $item -> idn, 'file_name' => 'meta.xml'])));
                $format = $item_tag -> appendChild($xml -> createElement('format'));
                $format -> appendChild($xml -> createTextNode('xml'));
            }
            else
            {
                $link -> appendChild($xml -> createTextNode(url('opendata', ['idn' => $item -> idn, 'file_name' => 'meta.'.$extension_set])));
                $format = $item_tag -> appendChild($xml -> createElement('format'));
                $format -> appendChild($xml -> createTextNode($extension_set));
            }
        }
        $xml -> formatOutput = true;
        $temp_file = storage_path('app/opendata').'/reestr-xml-'.date('Ymd\THis').'.xml';
        $xml->save($temp_file);
        return response()->download($temp_file,'list.xml')->deleteFileAfterSend(true);
    }

    /**
     * @return mixed
     */
    static function json_reestr()
    {
        $data = OpenData::published()->get();
        $reestr = array(
            'standardversion' => 'http://opendata.gosmonitor.ru/standard/3.0',
            'meta' => [

            ]
        );

        foreach ($data as $i => $item)
        {
            if(is_null($item->set->last()->table)) {
                $file_set = $item->set->last()->getMedia('set')->first();
                $file_path_set = storage_path('app/opendata').$file_set->getUrl();
                $extension_set = pathinfo($file_path_set, PATHINFO_EXTENSION);
            } else {
                $extension_set = 'json';
            }
            $reestr['meta'][$i]['identifier'] = $item -> idn;
            $reestr['meta'][$i]['title'] = $item -> title;
            if($extension_set == 'xlsx' || $extension_set == 'xls')
            {
                $reestr['meta'][$i]['link'] = url('opendata', ['idn' => $item -> idn, 'file_name' => 'meta.json']);
                $reestr['meta'][$i]['format'] = 'json';
            }
            else
            {
                $reestr['meta'][$i]['link'] = url('opendata', ['idn' => $item -> idn, 'file_name' => 'meta.'.$extension_set]);
                $reestr['meta'][$i]['format'] = $extension_set;
            }
        }

        $temp_file = storage_path('app/opendata').'/reestr-json-'.date('Ymd\THis').'.json';
        $file = fopen($temp_file, 'w');
        fwrite($file, json_encode($reestr));
        fclose($file);
        return response()->download($temp_file,'list.json')->deleteFileAfterSend(true);
    }
}