<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="content[@id='components_table']" priority="1">
<div class="container my-4">
	<noscript><div class="alert alert-danger" role="alert">The component editor requires JavaScript in order to function.</div></noscript>
	<div class="card border-dark">
		<div class="card-header text-white bg-dark">
			<a class="float-right btn btn-primary" href="component_edit.php?compId=new">Create Component</a>
			<h4 class="card-title">Page Components</h4>
			<h6 class="card-subtitle text-muted">Collections of page content that can be added to pages as a group.</h6>
		</div>
		<div class="card-body p-0">
			<div class="table-responsive"><table class="table table-hover m-0">
				<thead class="thead-light">
					<tr>
						<th scope="col">#</th>
						<th scope="col">Title</th>
						<th scope="col">Linked Files</th>
						<th scope="col">Used In</th>
						<th scope="col"><span class="sr-only">(controls)</span></th>
					</tr>
				</thead>
				<tbody>
				<xsl:for-each select="content[@class='Text']">
					<tr>
						<td><xsl:value-of select="index"/></td>
						<td><xsl:value-of select="title"/></td>
						<td class="py-1"><xsl:for-each select="css | js | xsl"><small style="display:block;"><xsl:value-of select="."/></small></xsl:for-each></td>
						<td class="py-1">
							<xsl:for-each select="in_page | in_component">
								<small style="display:block;"><xsl:value-of select="index"/>. <xsl:value-of select="title"/><xsl:if test="url"> (/<xsl:value-of select="url"/>)</xsl:if></small>
							</xsl:for-each>
						</td>
						<td><div class="btn-group btn-group-sm" role="group" aria-label="Component controls">
							<a href="component_edit.php?compId={index}" class="btn btn-light fas fa-edit" aria-label="Edit"></a>
							<button type="button" class="btn btn-light fas fa-trash" data-toggle="modal" data-target="#confirm_delete" data-comptitle="#{index} {title}" data-deleteurl="component_edit.php?compId={index}&amp;confirmdelete" aria-label="Delete"></button>
						</div></td>
					</tr>
				</xsl:for-each>
				</tbody>
			</table></div>
		</div>
	</div>
</div>
<div class="modal fade variable" id="confirm_delete" tabindex="-1" role="dialog" aria-labelledby="confirm_delete_label" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="confirm_delete_label">Confirm Delete</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span class="fas fa-times" aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body">
				<p>Are you sure you want to delete component <tt class="variable-content" data-variable="comptitle"></tt>?</p>
				<p>It is being used on ??? pages.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Don't Delete</button>
				<a href="#" class="btn btn-danger variable-attribute" data-variable="deleteurl" data-var-attr="href">Delete</a>
			</div>
		</div>
	</div>
</div>
</xsl:template>

</xsl:stylesheet>
