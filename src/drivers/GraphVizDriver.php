<?php
/**
 * Implementation of a call graph generation strategy wich renders a graph with dot.
 *
 * PHP version 5
 *
 * This file is part of PHPCallGraph.
 *
 * PHPCallGraph is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * PHPCallGraph is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    PHPCallGraph
 * @author     Falko Menge <fakko at users dot sourceforge dot net>
 * @copyright  2007 Falko Menge
 * @license    http://www.gnu.org/licenses/gpl.txt GNU General Public License
 */

// reduce warnings because of PEAR dependency
error_reporting(E_ALL ^ E_NOTICE);

require_once 'CallgraphDriver.php';
require_once 'Image/GraphViz.php';

/**
 * implementation of a call graph generation strategy wich renders a graph with dot
 */
class GraphVizDriver implements CallgraphDriver {

    protected $outputFormat;
    protected $dotCommand;
    protected $useColor = true;
    protected $graph;
    protected $currentCaller = '';
    protected $internalFunctions;

    /**
     * @return CallgraphDriver
     */
    public function __construct($outputFormat = 'png', $dotCommand = 'dot') {
        $this->initializeNewGraph();
        $this->setDotCommand($dotCommand);
        $this->setOutputFormat($outputFormat);
        $functions = get_defined_functions();
        $this->internalFunctions = $functions['internal'];
        
    }

    /**
     * @return void
     */
    public function reset() {
        $this->initializeNewGraph();
    }

    /**
     * @return void
     */
    protected function initializeNewGraph() {
        $this->graph = new Image_GraphViz();
        $this->graph->dotCommand = $this->dotCommand;
    }

    /**
     * Sets path to GraphViz/dot command
     * @param string $dotCommand Path to GraphViz/dot command
     * @return void
     */
    public function setDotCommand($dotCommand = 'dot') {
        $this->dotCommand = $dotCommand;
        $this->graph->dotCommand = $dotCommand;
    }

    /**
     * Sets output format
     * @param string $outputFormat One of the output formats supported by GraphViz/dot
     * @return void
     */
    public function setOutputFormat($outputFormat = 'png') {
        $this->outputFormat = $outputFormat;
    }

    /**
     * Enables or disables the use of color
     * @param boolean $boolean True if color should be used
     * @return void
     */
    public function setUseColor($boolean = true) {
        $this->useColor = $boolean;
    }

    /**
     * @param integer $line
     * @param string $file
     * @param string $name
     * @return void
     */
    public function startFunction($line, $file, $name) {
        $this->addNode($name);
        $this->currentCaller = $name;
    }

    /**
     * @param integer $line
     * @param string $file
     * @param string $name
     * @return void
     */
    public function addCall($line, $file, $name) {
        $this->addNode($name);
        $this->graph->addEdge(array($this->currentCaller => $name));
    }

    /**
     * @return void
     */
    protected function addNode($name) {
        $nameParts = explode('::', $name);
        $cluster = 'default';
        $label = $name;
        $color = 'lightblue2';
        if (count($nameParts) == 2) {
            if (empty($nameParts[0])) {
                $cluster = 'class is unknown';
            } else {
                $cluster = $nameParts[0];
            }
            $label = $nameParts[1];
        }
        $label = substr($label, 0, strpos($label, '(')); 
        if (in_array($label, $this->internalFunctions)) {
            $cluster = 'internal PHP functions';
        }
        $this->graph->addNode(
            $name,
            array(
                'label' => $label,
                'style' => ($this->useColor ? 'filled' : ''),
                'color' => $color,
                ),
            $cluster
            );
        //*
        $this->graph->addCluster(
            $cluster,
            $cluster,
            array(
//                'style' => ($this->useColor ? 'filled' : ''),
//                'color' => 'slateblue',
                )
            );
        //*/
    }

    /**
     * @return void
     */
    public function endFunction() {
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->graph->fetch($this->outputFormat);
    }
}
?>