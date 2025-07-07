<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
        <?php $donations_list_table->search_box('Cari Donasi', 'search_id'); ?>
        <?php $donations_list_table->display(); ?>
    </form>
</div>