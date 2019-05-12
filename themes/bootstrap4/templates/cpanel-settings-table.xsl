<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="content[@id='settings_table']" priority="1">
<div class="container my-4">
	<div class="card border-dark">
		<div class="card-header text-white bg-dark">
			<h4 class="card-title">Site Settings</h4>
			<h6 class="card-subtitle text-muted">Properties that determine how the site will load and appear.</h6>
		</div>
		<div class="card-body p-0">
			<div class="table-responsive"><table class="table table-hover m-0">
				<thead class="thead-light">
					<tr>
						<th scope="col">Setting</th>
						<th scope="col">Value</th>
						<th scope="col" class="d-lg-table-cell d-none">Description</th>
					</tr>
				</thead>
				<tbody>
				<xsl:for-each select="content[@class='Container']">
					<tr>
						<td scope="row"><label class="form-text h6" for="value{@id}"><xsl:value-of select="content[@id='setting']"/></label></td>
						<td><xsl:apply-templates select="content[@id='value']"><xsl:with-param name="id">value<xsl:value-of select="@id"/></xsl:with-param></xsl:apply-templates></td>
						<td class="d-lg-table-cell d-none"><small><xsl:value-of select="content[@id='description']"/></small></td>
					</tr>
					<tr>
						<td colspan="3" class="d-lg-none d-table-cell border-top-0 pt-0"><small><xsl:value-of select="content[@id='description']"/></small></td>
					</tr>
				</xsl:for-each>
				</tbody>
			</table></div>
		</div>
	</div>
</div>
</xsl:template>

</xsl:stylesheet>
