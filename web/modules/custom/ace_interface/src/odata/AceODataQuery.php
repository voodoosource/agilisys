<?php
/**
 * Created by PhpStorm.
 * User: agilisys
 * Date: 21/03/2019
 * Time: 20:08
 */

namespace Drupal\ace_interface\odata;


class AceODataQuery {

    private $table;
    private $record;
    private $fields;
    private $filters;
    private $orderBy;
    private $expand;
    private $top;

    public function __construct($table, $record = null) {
        $this->fields = array();
        $this->filters = array();
        $this->expand = array();
        $this->table = $table;
        $this->record = $record;
        $this->top = null;
        $this->orderBy = array();
    }

    public function fields($fields) {
        if ($fields) {
            foreach ($fields as $field) {
                $this->fields[] = $field;
            }
        }
    }

    public function filter($field, $value) {
        $this->filters[$field] = $value;
    }

    public function order($orderBy) {
        $this->orderBy = $orderBy;
    }

    public function expand($expand) {
        $this->expand = $expand;
    }

    public function top($top) {
        $this->top = $top;
    }

    public function buildQuery() {

        $haveParams = false;
        $filters = null;
        $fields = null;
        $expand = null;
        $top = null;
        $order = null;

        if (count($this->fields)) {
            $fields = $this->buildParameter('select', $this->buildFields());
            $haveParams = true;
        }
        if (count($this->filters)) {
            $filters = $this->buildParameter('filter', $this->buildFilters());
            $haveParams = true;
        }
        if ($this->expand) {
            $expand = $this->buildParameter('expand', $this->buildExpand());
            $haveParams = true;
        }
        if ($this->top) {
            $top = $this->buildParameter('top', $this->top);
            $haveParams = true;
        }
        if ($this->orderBy && count($this->orderBy)) {
            $order = $this->buildParameter('orderby', $this->buildOrderBy());
        }

        $output = $this->table;

        $output .= $this->buildRecord();

        if ($haveParams) $output .= '?';

        $queryOptions = [
            'fields' => $fields,
            'expand' => $expand,
            'filters' => $filters,
            'top' => $top,
            'order_by' => $order,
        ];

        $output = $this->buildQueryOptions($output, $queryOptions);

        return $output;

    }

    private function buildQueryOptions($output, $options) {
        $i = 0;
        foreach ($options as $key => $option) {

            if ($option) {
                if ($i > 0) {
                    $output .= '&';
                }

                $output .= $option;

                $i++;
            }
        }

        return $output;
    }

    private function buildParameter($field, $value) {
        if ($value) {
            return '$' . $field . '=' . $value;;
        }
    }

    private function buildRecord() {
        //Add record
        if ($this->record) {
            return '(' . $this->record . ')';
        } else {
            return '';
        }

    }

    private function buildFields() {
        $output = '';

        //Add fields
        if (count($this->fields)) {

            foreach ($this->fields as $key => $value) {

                $output .= $value;

                if ($key < count($this->fields) - 1) {
                    $output .= ',';
                }

            }

        }

        return $output;

    }

    private function buildOrderBy() {
        $output = '';

        $i = 0;

        if (count($this->orderBy)) {
            foreach ($this->orderBy as $key => $order) {
                $output .= $order['field'] . ' ' . $order['direction'];

                if ($i++ < sizeof($this->orderBy) - 1) {
                    $output .= ',';
                }
            }
        }

        return $output;
    }

    private function buildFilters() {
        $output = '';

        //Add filters
        if (count($this->filters)) {

            $index = 1;
            $quote = "'";


            foreach ($this->filters as $key => $value) {

                if ($value === "" || $value === null) {
                    continue;
                }

                //Int values don't need to be wrapped in quotes
                if (is_int($value)) {
                    $output .= '(' . $key . '+eq+' . $value . ')';
                } elseif(is_bool($value)){
                    $value = $value ? "true" : "false";
                    $output .= '(' . $key . '+eq+' . $value . ')';
                } else {
                    switch ($key) {
                        case 'cxm_languageculture':
                            $output .= '(' . $key . '+eq+' . $value . ')';
                            break;
                        case 'cxm_name':
                            $output .= '(' . $key . '+eq+' . $value . ')';
                            break;
                        case 'sog_pingid':
                            $output .= '(' . $key . '+eq+' . $quote . $value . $quote . ')';
                            break;
                        default:
                            //GUID values and do not need to be wrapped in quotes.
                            $guidRegex = '/(\{){0,1}[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}(\}){0,1}/m';

                            if (preg_match_all($guidRegex, $value, $matches, PREG_SET_ORDER, 0)) {
                                $output .= '(' . $key . '+eq+' . $value . ')';
                            } else {
                                $output .= '(' . $key . '+eq+' . $quote . $value . $quote . ')';
                            }
                            break;
                    }
                }


                if ($index < count($this->filters)) {
                    $output .= ' and ';
                }
                $index++;

            }

        }
        return $output;
    }

    private function buildExpand() {
        $output = '';

        //Add expand links
        if ($this->expand) {
            $i = 0;
            foreach ($this->expand as $fieldToExpand => $selectFromExpand) {
                $expandFieldsOutput = '';
                if ($selectFromExpand) {
                    $j = 0;
                    foreach ($selectFromExpand as $expandField) {
                        $expandFieldsOutput .= $expandField;

                        if ($j++ < sizeof($selectFromExpand) - 1) {
                            $expandFieldsOutput .= ',';
                        }
                    }
                    $output .= $fieldToExpand . '($select=' . $expandFieldsOutput . ')';
                } else {
                    $output .= $fieldToExpand;
                }

                $sizeof = sizeof($this->expand);
                if ($i++ < sizeof($this->expand) - 1) {
                    $output .= ',';
                }
            }
        }

        return $output;
    }

}

