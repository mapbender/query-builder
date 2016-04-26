<?php

namespace Mapbender\QueryBuilderBundle\Util;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class HtmlExportResponse
 *
 * @package Mapbender\DataSourceBundle\Util
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class HtmlExportResponse extends Response
{
    /** @var array */
    private $results;

    /** @var null|string */
    private $title;

    public $css = /** @lang CSS */
        "
table{
    width: 100%;
}
       table, tbody, tr, td{
          padding: 0;
          margin: 0;
          border: 0;
       }
       th{
        background-color: #c0c0c0;
       }
       td{
            border: 1px solid #c0c0c0
       }
    ";

    /**
     * HtmlExportResponse constructor.
     *
     * @param array       $results
     * @param null|string $title
     */
    public function __construct(array &$results, $title = null)
    {
        parent::__construct();
        $this->title    = $title;
        $this->results  = &$results;
    }

    /**
     * Gets the current response content.
     *
     * @return string Content
     */
    public function getContent()
    {
        $content   = array();
        $content[] = $this->genTitle($this->title);
        $content[] = $this->genTable($this->results);

        return /** @lang XHTML */
            "<html>
                    <head>
                        <title>" . $this->title . "</title>
                        <style media='all' type='text/css' >".$this->css."</style>
                    </head>
                    <body>" . implode($content) . "</body>
               </html>";
    }

    /**
     * @param array $list
     * @return string
     */
    public function genTable(array &$list)
    {
        $rows = array();
        foreach ($list as &$item) {
            $rows[] = $this->genRow($item);
        }

        return "<table>" . $this->genTableHead($list) . implode($rows) . "</table>";
    }


    /**
     * @param $title
     * @return string
     */
    public function genTitle($title)
    {
        return "<h1>$title</h1>";
    }

    /**
     * @param $item
     * @return string
     */
    private function genRow(&$item)
    {
        $cells = array();
        foreach ($item as &$value) {
            $cells[] = $this->genCell($value);
        }
        return "<tr>" . implode("", $cells) . "</tr>";
    }

    /**
     * Generate cell
     *
     * @param $value
     * @return string
     */
    private function genCell(&$value)
    {
        return "<td>" . $value . "</td>";
    }

    /**
     * @param $list
     * @return string
     */
    private function genTableHead(&$list)
    {
        $r       = array();
        $hasRows = count($list) > 0;
        if ($hasRows) {
            $current = $list[0];
            foreach ($current as $k => &$v) {
                $r[] = "<th>".$k."</th>";
            }
        }
        return  "<tr>" . implode($r) . "</tr>";
    }
}