<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="content[@id='pages_table']" priority="1">
<div class="container my-4">
	<noscript><div class="alert alert-danger" role="alert">The page editor requires JavaScript in order to function.</div></noscript>
	<div class="card border-dark">
		<div class="card-header text-white bg-dark">
			<a class="float-right btn btn-primary ml-2" href="page_edit.php?pageId=new">Create Page</a>
			<a class="float-right btn btn-primary ml-2" href="page_edit.php?pageId=file">Add PHP File</a>
			<h4 class="card-title">Normal Pages</h4>
			<h6 class="card-subtitle text-muted">User-defined pages that are normally accessible on your site.</h6>
		</div>
		<div class="card-body p-0">
			<div class="table-responsive"><table class="table table-hover m-0">
				<thead class="thead-light">
					<tr>
						<th scope="col">#</th>
						<th scope="col">Title</th>
						<th scope="col">URL</th>
						<th scope="col">Subtheme</th>
						<th scope="col">Linked Files</th>
						<th scope="col">Permissions</th>
						<th scope="col"><span class="sr-only">(controls)</span></th>
					</tr>
				</thead>
				<tbody>
				<xsl:for-each select="content[@class='Text']">
					<tr>
						<td><xsl:value-of select="index"/></td>
						<td><xsl:value-of select="title"/></td>
						<td><xsl:value-of select="url"/></td>
						<td><xsl:element name="span">
							<xsl:attribute name="class"><xsl:if test="subtheme[@invalid]">text-warning</xsl:if> <xsl:if test="subtheme[@default]">font-italic</xsl:if></xsl:attribute>
							<xsl:value-of select="subtheme"/>
						</xsl:element></td>
						<td class="py-1">
							<xsl:for-each select="css | js | xsl"><small style="display:block;"><xsl:value-of select="."/></small></xsl:for-each>
							<xsl:if test="file"><small style="display:block;"><xsl:value-of select="file"/></small></xsl:if>
						</td>
						<td><xsl:for-each select="permission">
							<xsl:value-of select="."/>
							<xsl:if test="last()!=position()">,</xsl:if>
						</xsl:for-each></td>
						<td><div class="btn-group btn-group-sm" role="group" aria-label="Page controls">
							<a href="../{url}" target="_blank" class="btn btn-light fas fa-eye" aria-label="View"></a>
							<a href="page_edit.php?pageId={index}" class="btn btn-light fas fa-edit" aria-label="Edit"></a>
							<button type="button" class="btn btn-light fas fa-trash" data-toggle="modal" data-target="#confirm_delete" data-pagetitle="#{index} {title} (/{url})" data-deleteurl="page_edit.php?pageId={index}&amp;confirmdelete" aria-label="Delete"></button>
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
				<p>Are you sure you want to delete page <tt class="variable-content" data-variable="pagetitle"></tt>?</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Don't Delete</button>
				<a href="#" class="btn btn-danger variable-attribute" data-variable="deleteurl" data-var-attr="href">Delete</a>
			</div>
		</div>
	</div>
</div>
</xsl:template>

<xsl:template match="content[@id='special_pages_table']" priority="1">
<div class="container my-4">
	<div class="card border-dark">
		<div class="card-header text-white bg-dark">
			<h4 class="card-title">Special Pages</h4>
			<h6 class="card-subtitle text-muted">System pages that only appear in special circumstances.</h6>
		</div>
		<div class="card-body p-0">
			<div class="table-responsive"><table class="table table-hover m-0">
				<thead class="thead-light">
					<tr>
						<th scope="col">Type</th>
						<th scope="col">Title</th>
						<th scope="col">Subtheme</th>
						<th scope="col">Linked Files</th>
						<th scope="col"><span class="sr-only">(controls)</span></th>
					</tr>
				</thead>
				<tbody>
				<xsl:for-each select="content[@class='Text']">
					<tr>
						<td><xsl:value-of select="type"/></td>
						<td><xsl:value-of select="title"/></td>
						<td><xsl:element name="span">
							<xsl:attribute name="class"><xsl:if test="subtheme[@invalid]">text-warning</xsl:if> <xsl:if test="subtheme[@default]">font-italic</xsl:if></xsl:attribute>
							<xsl:value-of select="subtheme"/>
						</xsl:element></td>
						<td class="py-1"><xsl:for-each select="css | js | xsl"><small style="display:block;"><xsl:value-of select="."/></small></xsl:for-each></td>
						<td><div class="btn-group btn-group-sm" role="group" aria-label="Page controls">
							<a href="../index.php?specialPage={index}" target="_blank" class="btn btn-light fas fa-eye" aria-label="View"></a>
							<a href="page_edit.php?specialPageId={index}" class="btn btn-light fas fa-edit" aria-label="Edit"></a>
						</div></td>
					</tr>
				</xsl:for-each>
				</tbody>
			</table></div>
		</div>
	</div>
</div>
</xsl:template>

</xsl:stylesheet>
