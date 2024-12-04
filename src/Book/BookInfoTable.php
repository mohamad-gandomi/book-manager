<?php

namespace RabbitBookManager\Book;

use WP_List_Table;
use wpdb;

class BookInfoTable extends WP_List_Table
{
    private $wpdb;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'book_info', // Singular label
            'plural'   => 'books_info', // Plural label
            'ajax'     => false
        ]);

        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function get_columns()
    {
        return [
            'ID'        => 'ID',
            'post_id'   => 'Post ID',
            'isbn'      => 'ISBN',
        ];
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = [];

        $this->_column_headers = [$columns, $hidden, $sortable];

        // Get the data from the `books_info` table
        $data = $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}books_info");

        $this->items = $data;
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'ID':
            case 'post_id':
            case 'isbn':
                return $item->$column_name;
            default:
                return print_r($item, true);
        }
    }
}
