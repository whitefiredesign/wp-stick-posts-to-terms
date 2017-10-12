<?php
// Get all stickies
try {
    $stickies = StickyPost::get_stickies('query');
} catch (Exception $e) {
    $stickies = false;
}

// If Saving
$feedback = false;
if(isset($_POST['settings'])) {
    $settings = $_POST['settings'];
    
    // If purge stickies
    if(isset($settings['purge-stickies'])) {
        try {
            $this->purge_stickies($settings['purge-stickies']);
        } catch(Exception $e) {
            $feedback = array(
                'status' => 'error',
                'message'=> $e->getMessage()
            );
        }

        $feedback = array(
            'status' => 'success',
            'message'=> 'Successfully Saved!'
        );
    }

}
?>

<div class="wrap fuse">
    <h1>StickyPost Settings</h1>

    <?php if($feedback) { ?>
        <div class="fuse-alert sp-fb-msg <?php echo $feedback['status']; ?>">
            <button type="button" class="close" data-dismiss="sp-fb-msg" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?php echo $feedback['message']; ?>
        </div>
    <?php } ?>

    <?php if($stickies && $stickies->have_posts()) { ?>
        <h2>Current list of stickies</h2>
        <table class="wp-list-table widefat fixed striped pages">
            <thead>
            <tr>
                <th>ID</th><th>Title</th><th>Placement</th>
            </tr>
            </thead>
            <tbody>
            <?php while($stickies->have_posts()) { ?>
                <?php $stickies->the_post(); ?>
                <?php $stickyPlacement = StickyPost::get_sticky_placement(get_the_ID()); ?>
                <tr>
                    <td><?php echo get_the_ID(); ?></td>
                    <td><?php the_title(); ?></td>
                    <td><?php 
                        if($stickyPlacement) { 
                            echo $stickyPlacement; 
                        } else {
                            echo StickyPost::home_sticky_placement();
                        }
                        ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    <?php } ?>

    <h2>Purge stickies from database</h2>
    <form method="post" action="">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="purge_term_stickies">Purge Stickies?</label>
                </th>
                <td>
                    <button type="submit" name="settings[purge-stickies]" class="button fuse-branded" value="terms">Terms</button>
                    <button type="submit" name="settings[purge-stickies]" class="button fuse-branded" value="home">Homepage</button>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
</div>