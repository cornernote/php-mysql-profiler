<?php
/**
 * PHP MySQL Profiler
 *
 * Copyright (c) 2012 Brett O'Donnell <brett@mrphp.com.au>
 * Source Code: https://github.com/cornernote/php-mysql-profiler
 * Home Page: http://mrphp.com.au/blog/php-mysql-profiler
 * License: GPLv3
 */

class mysql_profile
{
    /**
     * @var bool
     */
    var $backtrace = false;
    /**
     * @var array
     */
    var $timer = array();

    /**
     * @param $query
     * @param null $link
     * @return resource
     */
    function mysql_query($query, $link = null)
    {
        $t = array();
        $t['query'] = $query;
        $t['start'] = $this->timer();
        $result = mysql_query($query, $link);
        $t['end'] = $this->timer();
        $t['timer'] = $t['end'] - $t['start'];
        $t['trace'] = $this->backtrace ? $this->debug_backtrace() : null;
        $this->timer[] = $t;
        return $result;
    }

    /**
     * @return float
     */
    function timer()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * @return array
     */
    function debug_backtrace()
    {
        $path = dirname(dirname(dirname(__FILE__)));
        $files = debug_backtrace();
        array_shift($files);
        foreach ($files as $k => $file) {
            $files[$k]['file'] = str_replace($path, '', $file['file']);
        }
        return $files;
    }

    /**
     *
     */
    function profile()
    {
        $total = 0;
        $rows = array();
        foreach ($this->timer as $i => $q) {
            $total += $q['timer'];
            $row = '';
            $row .= '<tr>';
            $row .= '<td>' . ($i + 1) . '</td>';
            if ($q['timer'] > 1) {
                $row .= '<td style="color:red;font-weight:bold;">' . $q['query'] . '</td>';
            }
            elseif ($q['timer'] > 0.5) {
                $row .= '<td style="color:red;">' . $q['query'] . '</td>';
            }
            elseif ($q['timer'] > 0.3) {
                $row .= '<td style="color:blue;font-weight:bold;">' . $q['query'] . '</td>';
            }
            elseif ($q['timer'] > 0.1) {
                $row .= '<td style="color:blue;">' . $q['query'] . '</td>';
            }
            else {
                $row .= '<td style="color:green;">' . $q['query'] . '</td>';
            }
            $row .= '<td>' . round($q['timer'], 3) . '</td>';
            $row .= '<td><ol>';
            if (isset($q['trace']) && $q['timer'] > 0.1) {
                foreach ($q['trace'] as $file) {
                    $row .= '<li>';
                    $row .= $file['file'] . ' line ' . $file['line'];
                    if (isset($file['function'])) {
                        $row .= ' - ' . $file['function'] . '()';
                        if (isset($file['args']) && !empty($file['args'])) {
                            $row .= '<ol>';
                            foreach ($file['args'] as $arg) {
                                $row .= '<li>';
                                $row .= $arg;
                                $row .= '</li>';
                            }
                            $row .= '</ol>';
                        }
                    }
                    $row .= '</li>';
                }
            }
            $row .= '</ol></td>';
            $row .= '</tr>';
            $rows[] = $row;
        }
        if (isset($i)) {
            echo '<table>';
            echo '<tr>';
            echo '<th colspan="4" align="center">' . ($i + 1) . ' queries took ' . round($total, 3) . ' seconds</th>';
            echo '</tr>';
            echo implode("\r", $rows);
            echo '</table>';
        }
    }

}