
<?php


//mail("kontakt@creativehead.pl","My subject", 'test123');
//
//die();

//ini_set('max_execution_time', 600);
//ini_set('memory_limit','1024M');

$string = 'minicart-content-wrapper';
searchinfiles('/app/', $string);
searchinfiles('/vendor/magento/', $string);


function searchinfiles($path,$string)
{

    $source = (__DIR__).$path;
//    echo $source.'<br/>';

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file)
        {
            $file = str_replace('\\', '/', $file);

            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                continue;

            $file = realpath($file);

            if (is_dir($file) === true)
            {

            }
            else if (is_file($file) === true)
            {

//                if (strpos(($file), $string) !== false) {
//                    echo $file."\n";
//                }
                if (strpos(file_get_contents($file), $string) !== false) {
                    echo $file.'<br/>'."\n";
//                    //echo '<textarea style="width: 100%; min-height: 200px; margin-bottom: 50px">'.file_get_contents($file).'</textarea>';
//                    //echo '<br/>';
                };
            }
        }
    }
}
