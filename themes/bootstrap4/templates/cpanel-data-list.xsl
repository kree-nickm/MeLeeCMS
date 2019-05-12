<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="content[@id='table_list']" priority="1">
<div class="container my-4">
	<div class="card border-dark">
		<div class="card-header text-white bg-dark">
			<h4 class="card-title">Site Data</h4>
			<h6 class="card-subtitle text-muted">List of custom database tables containing custom site data.</h6>
		</div>
		<div class="card-body p-0">
			<div class="table-responsive"><table id="database_table" class="table table-hover m-0">
				<thead class="thead-light">
					<tr>
						<th scope="col">Table</th>
						<th scope="col">Columns</th>
						<th scope="col">Rows</th>
						<th scope="col"><span class="sr-only">(controls)</span></th>
					</tr>
				</thead>
				<tbody>
				<xsl:for-each select="content[@class='Container']">
					<tr>
						<td scope="row"><xsl:value-of select="content[@id='table']"/></td>
						<td><xsl:value-of select="content[@id='cols']"/></td>
						<td><xsl:value-of select="content[@id='rows']"/></td>
						<td>
							<button type="button" class="btn btn-secondary fas fa-info" data-toggle="modal" data-target="#modal{@id}" aria-label="Details"></button>
						</td>
					</tr>
				</xsl:for-each>
				</tbody>
			</table></div>
			<nav aria-label="Changes page navigation">
				<ul class="pagination my-2 justify-content-center">
					<xsl:for-each select="content[@id='pages']/page">
						<xsl:element name="li">
							<xsl:attribute name="class">page-item<xsl:if test="@disabled"> disabled</xsl:if><xsl:if test="@current"> active</xsl:if></xsl:attribute>
							<a class="page-link" href="?p={@number}"><xsl:value-of select="."/></a>
						</xsl:element>
					</xsl:for-each>
				</ul>
			</nav>
		</div>
	</div>
</div>
</xsl:template>

</xsl:stylesheet>
