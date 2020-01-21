<footer class="short">
    <section id="contact_info" class="contact_info">
        <?php $git_hash_string = !empty($this->git_hash) ? " [{$this->git_hash}]" : ""; ?>
        <div id="last_update_timestamp" class="last_update_timestamp" style="display: flex; flex-direction: row-reverse">
            <span class="fa-stack fa-1x info-icon">
              <i class="fa fa-circle fa-stack-2x info-icon-background"></i>
              <i class="fa fa-circle-thin fa-stack-2x info-icon-background-ring"></i>
              <i class="fa fa-info fa-stack-1x"></i>
            </span>
            <span class="update_timestamp_text">Version <?php echo $this->application_version ?><?php echo $git_hash_string ?> / Updated <?php echo $this->last_update_time->format('n/j/Y g:i a') ?></span>
        </div>
    </section>
</footer>
