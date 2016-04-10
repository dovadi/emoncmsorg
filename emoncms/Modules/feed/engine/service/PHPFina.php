<?php

// This timeseries engine implements:
// Fixed Interval No Averaging

class RemotePHPFina
{
    private $path;

    public function __construct($path)
    {
        $this->path = $path;
    }
    
    public function create($id,$options)
    {
        $interval = $options['interval'];
        return file_get_contents($this->path."create?id=".$id."&interval=".$interval);
    }
    
    public function post($id,$timestamp,$value)
    {
        // return file_get_contents($this->path."post?id=".$id."&time=".$timestamp."&value=".$value);
        return $value;
    }
    
    public function update($id,$timestamp,$value)
    {
        // return file_get_contents($this->path."update?id=".$id."&time=".$timestamp."&value=".$value);
        return $value;
    }
    
    public function get_data_new($id,$start,$end,$interval,$skipmissing,$limitinterval)
    {
        $out = "";
        if ($source = @fopen($this->path."datanew?id=".$id."&start=".$start."&end=".$end."&interval=".$interval."&skipmissing=".$skipmissing."&limitinterval=".$limitinterval,'r'))
        {
            for (;;)
            {
                $out .= fread($source,8192);
                if (feof($source)) break;
            }
        }
    
        return json_decode($out);
    }
    
    public function get_data_DMY($id,$start,$end,$mode,$timezone)
    {
        $out = "";
        if ($source = @fopen($this->path."dataDMY?id=".$id."&start=".$start."&end=".$end."&mode=".$mode."&timezone=".$timezone,'r'))
        {
            for (;;)
            {
                $out .= fread($source,8192);
                if (feof($source)) break;
            }
        }
    
        return json_decode($out);
    }
    
    public function get_data($id,$start,$end,$interval)
    {
        $out = "";
        if ($source = @fopen($this->path."data?id=".$id."&start=".$start."&end=".$end."&interval=".$interval,'r'))
        {
            for (;;)
            {
                $out .= fread($source,8192);
                if (feof($source)) break;
            }
        }
    
        return json_decode($out);
        
        
    }

    public function lastvalue($id)
    {
        $lastvalue = json_decode(file_get_contents($this->path."lastvalue?id=".$id));
        return array("time"=>$lastvalue->time, "value"=>$lastvalue->value);
    }
    
    public function export($id,$start)
    {
        // There is no need for the browser to cache the output
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Tell the browser to handle output as a csv file to be downloaded
        header('Content-Description: File Transfer');
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename={$id}.dat");

        header("Expires: 0");
        header("Pragma: no-cache");

        // Write to output stream
        $target = @fopen( 'php://output', 'w' );
        
        if ($source = @fopen($this->path."export?id=".$id."&start=".$start,'r'))
        {
            for (;;)
            {
                $data = fread($source,8192);
                fwrite($target,$data);
                if (feof($source)) break;
            }
        }
        fclose($source);
        fclose($target);
        exit;
    }
    
    public function delete($id)
    {
        return file_get_contents($this->path."delete?id=".$id);
    }
    
    public function get_feed_size($id)
    {
        return file_get_contents($this->path."size?id=".$id);
    }
    
    public function get_meta($id)
    {
        return json_decode(file_get_contents($this->path."meta?id=".$id));
    }
    
    public function csv_export($id,$start,$end,$interval)
    {
        // There is no need for the browser to cache the output
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Tell the browser to handle output as a csv file to be downloaded
        header('Content-Description: File Transfer');
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename={$id}.csv");

        header("Expires: 0");
        header("Pragma: no-cache");

        // Write to output stream
        $target = @fopen( 'php://output', 'w' );
        
        if ($source = @fopen($this->path."csvexport?id=".$id."&start=".$start."&end=".$end."&interval=".$interval,'r'))
        {
            for (;;)
            {
                $data = fread($source,8192);
                fwrite($target,$data);
                if (feof($source)) break;
            }
        }
        fclose($source);
        fclose($target);
        exit;
    }
}
