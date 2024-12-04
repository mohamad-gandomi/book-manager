<?php

namespace RabbitBookManager\Book;

use Rabbit\Contracts\BootablePluginProviderInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

class BookServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface, BootablePluginProviderInterface
{
    protected $provides = ['book-service'];

    public function register()
    {
        // Register services if necessary
    }

    public function boot()
    {
        $this->registerPostType();
        $this->registerMetaBox();
        $this->registerAdminPage();
    }

    private function registerPostType()
    {
        add_action('init', function () {
            register_post_type('book', [
                'label' => __('Books', 'book-manager'),
                'public' => true,
                'supports' => ['title', 'editor', 'thumbnail'],
                'has_archive' => true,
                'show_in_rest' => true,
            ]);

            register_taxonomy('publisher', 'book', [
                'label' => __('Publishers', 'book-manager'),
                'hierarchical' => true,
                'show_in_rest' => true,
            ]);

            register_taxonomy('authors', 'book', [
                'label' => __('Authors', 'book-manager'),
                'hierarchical' => true,
                'show_in_rest' => true,
            ]);
        });
    }

private function registerMetaBox()
{
    add_action('add_meta_boxes', function () {
        add_meta_box('isbn_meta_box', __('ISBN', 'book-manager'), function ($post) {
            $isbn = get_post_meta($post->ID, '_isbn', true);
            echo '<input type="text" name="isbn" value="' . esc_attr($isbn) . '" />';
        }, 'book');
    });

    add_action('save_post', function ($post_id) {
        // Verify that this is not an autosave or bulk edit.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check post type.
        if (get_post_type($post_id) !== 'book') {
            return;
        }

        // Check if the ISBN field is set and save it as post meta
        if (isset($_POST['isbn'])) {
            update_post_meta($post_id, '_isbn', sanitize_text_field($_POST['isbn']));

            // Now insert into the books_info table
            global $wpdb;
            $isbn = sanitize_text_field($_POST['isbn']);
            $post_id = $post_id;

            // Insert or update ISBN into the database table
            $table_name = $wpdb->prefix . 'books_info';

            // Check if the book already has an entry in the table
            $existing_entry = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $table_name WHERE post_id = %d", $post_id));

            if ($existing_entry) {
                // If the book already exists, update the entry
                $wpdb->update(
                    $table_name,
                    ['isbn' => $isbn],
                    ['post_id' => $post_id],
                    ['%s'],
                    ['%d']
                );
            } else {
                // Otherwise, insert a new entry
                $wpdb->insert(
                    $table_name,
                    [
                        'post_id' => $post_id,
                        'isbn' => $isbn
                    ],
                    ['%d', '%s']
                );
            }
        }
    });
}

    private function registerAdminPage()
    {
        add_action('admin_menu', function () {
            add_menu_page(
                __('Books Info', 'book-manager'),
                __('Books Info', 'book-manager'),
                'manage_options',
                'books-info',
                [$this, 'display_books_info'],
                'dashicons-book',
            );
        });
    }

    public function display_books_info()
    {
        // Include the custom table class and display it
        $table = new BookInfoTable();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Books Information', 'book-manager'); ?></h1>
            <form method="post">
                <?php
                $table->display();
                ?>
            </form>
        </div>
        <?php
    }

    public function bootPlugin()
    {
        $this->getContainer()::macro('book-service', function () {
            return $this->getContainer()->get('book-service');
        });
    }
}
