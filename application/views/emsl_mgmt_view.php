<?php
  $table_object = !empty($table_object) ? $table_object : "";
  $this->template_version = $this->config->item('template');
  $this->load->view("{$this->template_version}_template/view_header");
  $js = isset($js) ? $js : "";

?>

      <div id="container">
        <div id="main" class="main">
          <div id="header_container" class="header_container">
            <h1 class="underline"><?= $page_header ?></h1>
            <div id="login_id_container" class="login_id_container">
              <em><?= $this->nav_info['current_page_info']['logged_in_user'] ?></em>
            </div>
          </div>
          <div class="form_container">



          </div>

          <div class="loading_progress_container status_messages" id="loading_status" style="display:none;">
            <span class="spinner">&nbsp;&nbsp;&nbsp;</span>
            <span id="loading_status_text">Loading...</span>
          </div>
          <div class="themed" id="item_info_container" style="margin-top:20px;"></div>
        </div>
      </div>
    <?php $this->load->view("{$this->template_version}_template/view_footer_short"); ?>
  </div>
<script type='text/javascript'>
//<![CDATA[
  <?= $js ?>
//]]>
</script>

</body>
</html>
