<?php
/**
 * Pacifica
 *
 * Pacifica is an open-source data management framework designed
 * for the curation and storage of raw and processed scientific
 * data. It is based on the [CodeIgniter web framework](http://codeigniter.com).
 *
 *  The Pacifica-Reporting module provides an interface for
 *  concerned and interested parties to view the current
 *  contribution status of any and all instruments in the
 *  system. The reporting interface can be customized and
 *  filtered streamline the report to fit any level of user,
 *  from managers through instrument operators.
 *
 *  This file contains a number of common functions related to
 *  file info and handling.
 *
 * PHP version 5.5
 *
 * @package Pacifica-reporting
 *
 * @author  Ken Auberry <kenneth.auberry@pnnl.gov>
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 *
 * @link http://github.com/EMSL-MSC/Pacifica-reporting
 */
?>
<div id="subBanner" style="position:relative;">
    <?php if(empty($banner_file)) : ?>
  <div class="banner_bar_background">
    <div class="banner_bar banner_bar_left banner_bar_<?= $this->site_color ?>">
      <div class='user_login_info'>Signed in as: <?= $logged_in_user ?></div>
    </div>
    <div class="banner_bar banner_bar_right banner_bar_grey">
      <div id="site_label"><?= ucwords($site_identifier) ?> Reporting</div>
      <div id="last_update_timestamp" class="last_update_timestamp" style="">Last Source Update: <?= $this->last_update_time->format('n/j/Y g:i a') ?></div>
    <?php if($_SERVER["SERVER_NAME"] == "wfdev30w.pnl.gov") : ?>
      <div id="site_status_notification">Development Version</div>
    <?php endif; ?>
    </div>
  </div>
    <?php else: ?>
    <div class='user_login_info'>Signed in as: <?= $logged_in_user ?></div>
    <img src="<?= $banner_path ?>" <?= $banner_dimensions?> alt="" />
    <?php endif; ?>
</div>
