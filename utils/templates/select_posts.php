<form>
<table class="table">
    <thead>
    <tr>
        <th>Type</th>
        <th>Modified</th>
        <th>Created</th>
    </tr>
    </thead>
    <tbody>
    <?php use Sledgehammer\Html;

    foreach ($posts as $i => $post): ?>
        <tr>
            <td><label><input name="posts[]" value="<?= Html::escape($post->id) ?>" type="checkbox"> <?= Html::escape($post->type) ?></label></td>
            <td><?= Html::escape($post->modified) ?></td>
            <td><?= Html::escape($post->date) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<input type="submit" value="Export">
</form>