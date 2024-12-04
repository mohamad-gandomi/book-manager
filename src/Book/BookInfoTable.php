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
            'singular' => __('book_info', 'book-manager'), // Singular label, translatable
            'plural'   => __('books_info', 'book-manager'), // Plural label, translatable
            'ajax'     => false
        ]);

        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Define the columns for the table.
     */
    public function get_columns()
    {
        return [
            'ID'      => esc_html__('ID', 'book-manager'),
            'post_id' => esc_html__('Post ID', 'book-manager'),
            'isbn'    => esc_html__('ISBN', 'book-manager'),
        ];
    }

    /**
     * Prepare the items for display.
     */
    public function prepare_items()
    {
        $columns  = $this->get_columns();
        $hidden   = []; // No hidden columns
        $sortable = []; // No sortable columns in this example

        $this->_column_headers = [$columns, $hidden, $sortable];

        $data = $this->wpdb->get_results("SELECT * FROM {$this->wpdb->prefix}books_info");

        // Sanitize all database results
        foreach ($data as &$item) {
            $item->ID      = intval($item->ID);
            $item->post_id = intval($item->post_id);
            $item->isbn    = sanitize_text_field($item->isbn);
        }

        $this->items = $data;
    }

    /**
     * Render a default column for the table.
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'ID':
            case 'post_id':
            case 'isbn':
                return esc_html($item->$column_name);
            default:
                return esc_html(print_r($item, true));
        }
    }
}
