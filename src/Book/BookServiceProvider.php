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

    /**
     * Register the custom post type and taxonomies.
     */
    private function registerPostType()
    {
        add_action('init', function () {
            // Register custom post type "book"
            register_post_type('book', [
                'label' => __('Books', 'book-manager'),
                'public' => true,
                'supports' => ['title', 'editor', 'thumbnail'],
                'has_archive' => true,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-book',
            ]);

            // Register taxonomy "publisher"
            register_taxonomy('publisher', 'book', [
                'label' => __('Publishers', 'book-manager'),
                'hierarchical' => true,
                'show_in_rest' => true,
            ]);

            // Register taxonomy "authors"
            register_taxonomy('authors', 'book', [
                'label' => __('Authors', 'book-manager'),
                'hierarchical' => true,
                'show_in_rest' => true,
            ]);
        });
    }

    /**
     * Register the meta box for ISBN.
     */
    private function registerMetaBox()
    {
        add_action('add_meta_boxes', function () {
            add_meta_box(
                'isbn_meta_box',
                __('ISBN', 'book-manager'),
                function ($post) {
                    $isbn = get_post_meta($post->ID, '_isbn', true);
                    echo '<label for="isbn">' . __('ISBN:', 'book-manager') . '</label>';
                    echo '<input type="text" name="isbn" id="isbn" value="' . esc_attr($isbn) . '" />';
                },
                'book',
                'side'
            );
        });

        add_action('save_post', function ($post_id) {
            // Verify this is not an autosave or bulk edit
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            // Check post type
            if (get_post_type($post_id) !== 'book') {
                return;
            }

            // Save or update ISBN meta data
            if (isset($_POST['isbn'])) {
                $isbn = sanitize_text_field($_POST['isbn']);
                update_post_meta($post_id, '_isbn', $isbn);

                // Insert or update in the `books_info` database table
                global $wpdb;
                $table_name = $wpdb->prefix . 'books_info';
                $existing_entry = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $table_name WHERE post_id = %d", $post_id));

                if ($existing_entry) {
                    $wpdb->update(
                        $table_name,
                        ['isbn' => $isbn],
                        ['post_id' => $post_id],
                        ['%s'],
                        ['%d']
                    );
                } else {
                    $wpdb->insert(
                        $table_name,
                        [
                            'post_id' => $post_id,
                            'isbn' => $isbn,
                        ],
                        ['%d', '%s']
                    );
                }
            }
        });
    }

    /**
     * Register the admin page for managing books info.
     */
    private function registerAdminPage()
    {
        add_action('admin_menu', function () {
            add_menu_page(
                __('Books Info', 'book-manager'),
                __('Books Info', 'book-manager'),
                'manage_options',
                'books-info',
                [$this, 'displayBooksInfo'],
                'dashicons-book'
            );
        });
    }

    /**
     * Render the books info admin page.
     */
    public function displayBooksInfo()
    {
        $table = new BookInfoTable();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Books Information', 'book-manager'); ?></h1>
            <form method="post">
                <?php $table->display(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Boot the plugin.
     */
    public function bootPlugin()
    {
        $this->getContainer()::macro('book-service', function () {
            return $this->getContainer()->get('book-service');
        });
    }
}
