 <table class="sp-event-blocks sp-data-table">
   <tbody>
     <?php
      foreach ($data as $event) {
        $date = 'F j, Y';
        $time = 'h:i A';
        $datetime = new DateTime($event['dateTime'], new DateTimeZone('UTC'));
      ?>
       <tr>
         <td>
           <span class="team-logo logo-odd" title="<?php echo $event['homeTeam']['name'] ?>">
             <a href="#">
               <img decoding="async" width="128" height="128" src="<?php echo $event['homeTeam']['crest']; ?>" class="attachment-sportspress-fit-icon size-sportspress-fit-icon wp-post-image" alt="">
             </a>
           </span>
           <span class="team-logo logo-even" title="<?php echo $event['awayTeam']['name'] ?>">
             <a href="#">
               <img decoding="async" width="128" height="128" src="<?php echo $event['awayTeam']['crest']; ?>" class="attachment-sportspress-fit-icon size-sportspress-fit-icon wp-post-image" alt="">
             </a>
           </span>
           <time class="sp-event-date">
             <a href="#"><?php echo esc_html($datetime->format($date)); ?></a>
           </time>
           <h5 class="sp-event-results">
             <a href="#"><span class="sp-result ok"><?php echo $event['homeTeam']['score']; ?></span> - <span class="sp-result"><?php echo $event['awayTeam']['score']; ?></span></a>
           </h5>
           <div class="sp-event-venue">
             <div><?php echo $event['venue']; ?></div>
           </div>
           <h4 class="sp-event-title" itemprop="name">
             <a href="#"><?php echo $event['homeTeam']['name'] . ' vs ' . $event['awayTeam']['name'] ?></a>
           </h4>

         </td>
       </tr>
     <?php
      }
      ?>
   </tbody>
 </table>