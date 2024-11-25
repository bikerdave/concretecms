<?php
defined('C5_EXECUTE') or die("Access Denied.");
?>
<section class="ccm-ui">
	<header>
        <h3><?=t('Locations')?></h3>
    </header>

    <div class="ccm-page-panel-locations row">
        <div class="col-sm-10">
            <form method="post" action="<?=$controller->action('submit')?>" data-dialog-form="location" data-panel-detail-form="location">
                <input type="hidden" name="cParentID" value="<?=$cParentID?>" />
                <?php
                if ($c->isGeneratedCollection() || $c->isPageDraft()) {
                    ?>
                    <h3 class="fw-light"><?=t('Current Canonical URL')?></h3>
                    <div class="breadcrumb">
                        <?php
                        if ($c->isPageDraft()) {
                            ?>
                            <?=t('None. Pages do not have canonical URLs until they are published.')?>
                            <?php
                        } else {
                            ?>
                            <?= Loader::helper('navigation')->getLinkToCollection($c, true) ?>
                            <?php
                        }
                        ?>
                    </div>

                    <?php
                } else {
                    ?>

                    <h5 class="mt-3"><?=t('URLs to this Page')?></h5>

                    <table class="mt-4 ccm-page-panel-detail-location-paths">
                        <thead>
                        <tr>
                            <?php
                            if (!$isHome) {
                                ?>
                                <th><?=t('Canonical')?></th>
                                <?php
                            }
                            ?>
                            <th style="width: 100%">
                                <?=t('Path')?>
                            </th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <button class="btn btn-secondary float-end mt-1 mb-2" type="button" data-action="add-url"><?=t('Add URL')?></button>

                    <div class="clearfix"></div>
                    <p class="text-end"><small class="text-muted"><?=t('Note: Additional page paths are not versioned.<br> They will be available immediately.')?></small></p>

                    <?php
                }
                ?>

                <?php
                if (isset($sitemap) && $sitemap) {
                    ?>
                    <input type="hidden" name="sitemap" value="1" />
                    <?php
                }
                ?>
            </form>
        </div>
    </div>
</section>

<style type="text/css">
	table.ccm-page-panel-detail-location-paths td {
		vertical-align: middle !important;
	}
</style>

<script type="text/template" id="pagePath-template">
	<tr>

		<% if (!isHome) {  %>
		<td style="text-align: center"><input type="radio" name="canonical" value="<%=row%>" <% if (isCanonical) { %>checked<% } %> /></td>
		<% } %>
		<td>
            <div class="input-group">
                <% if (isAutoGenerated) { %>
                    <input type="hidden" name="generated" value="<%=row%>">
                    <input type="hidden" name="path[<%=row%>]" value="<%=pagePath%>">
                <% } %>
                <input type="text" data-input="auto" class="form-control border-right-0" <% if (isAutoGenerated) { %>disabled<% } else { %>name="path[]"<% } %> value="<%=pagePath%>" />
                <span class="input-group-icon border-left-0  <% if (isAutoGenerated) { %>disabled<% } %>">
                    <i class="fas fa-link"></i>
                </span>
            </div>
        </td>
		<td>
        <% if (!isAutoGenerated) { %>
            <a href="#" data-action="remove-page-path" class="icon-link"><i class="fas fa-trash-alt"></i></a>
        <% } %>
        </td>
	</tr>
</script>
<div class="ccm-panel-detail-form-actions dialog-buttons">
    <button class="float-start btn btn-secondary" type="button" data-dialog-action="cancel" data-panel-detail-action="cancel"><?=t('Cancel')?></button>
    <button class="float-end btn btn-success" type="button" data-dialog-action="submit"><?= t('Save Changes') ?></button>
</div>

<script type="text/javascript">

var renderPagePath = _.template(
    $('script#pagePath-template').html()
);

$(function() {

    $('button[data-dialog-action="submit"]').on('click', function(e) {
        e.preventDefault();
        let formData = $('form[data-dialog-form="location"]').serialize();

        $.ajax({
            type: 'POST',
            url: <?= json_encode((string) $controller->action('check')) ?>,
            data: formData,
            success: (xhr) => {
                let response = JSON.parse(xhr);

                if (response.paths && response.paths.length) {
                    let pathsList = response.paths.map(path => `<li>${path}</li>`).join('');
                    let errorMessage = `<?= t('The following page paths already exist:') ?> <ul>${pathsList}</ul> <?= t('Do you want to continue?')?>`;

                    ConcreteAlert.confirm(errorMessage, function () {
                        $('form[data-dialog-form="location"]').submit();
                    });
                } else {
                    $('form[data-dialog-form="location"]').submit();
                }
            }
        });
    });


	$('form[data-panel-detail-form=location]').on('click', 'a[data-action=remove-page-path]', function(e) {
		e.preventDefault();
		$(this).closest('tbody').find('input[type=radio]:first').prop('checked', true);
		$(this).closest('tr').remove();
	});

	$('button[data-action=add-url]').on('click', function() {
		var rows = $('table.ccm-page-panel-detail-location-paths tbody tr').length;
		$('table.ccm-page-panel-detail-location-paths tbody').append(
			renderPagePath({
				isAutoGenerated: false,
				isCanonical: false,
				isHome: <?=intval($isHome)?>,
				pagePath: '',
				row: rows
			})
		);
	});


    // first, we render the URL as it would be displayed auto-generated
	$('table.ccm-page-panel-detail-location-paths tbody').append(
		renderPagePath({
			isAutoGenerated: <?=intval($autoGeneratedPath->isPagePathAutoGenerated())?>,
			isCanonical: <?=intval($autoGeneratedPath->isPagePathCanonical())?>,
			isHome: <?=intval($isHome)?>,
			pagePath: '<?=$autoGeneratedPath->getPagePath()?>',
			row: 0
		})
	);


    // now we loop through all the rest of the page paths
    <?php
    foreach ($paths as $i => $path) {
        ?>
        $('table.ccm-page-panel-detail-location-paths tbody').append(
            renderPagePath({
                isAutoGenerated: <?=intval($path->isPagePathAutoGenerated())?>,
                isCanonical: <?=intval($path->isPagePathCanonical())?>,
                isHome: <?=intval($isHome)?>,
                pagePath: '<?=preg_replace("/['\"\(\)\{\}\s]/", '', $path->getPagePath())?>',
                row: <?=$i + 1?>
            })
        );
        <?php
    } ?>


});
</script>
