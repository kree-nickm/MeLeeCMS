<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="content[@id='change_log']" priority="1">
<div class="container my-4">
	<div class="card border-dark">
		<div class="card-header text-white bg-dark">
			<h4 class="card-title">Change Log</h4>
			<h6 class="card-subtitle text-muted">List of recent changes made to rows of the MySQL database.</h6>
		</div>
		<div class="card-body p-0">
			<div class="table-responsive"><table id="change_log_table" class="table table-hover m-0">
				<thead class="thead-light">
					<tr>
						<th scope="col">Time</th>
						<th scope="col">Table</th>
						<th scope="col">Columns Updated</th>
						<th scope="col">New</th>
						<th scope="col">Blame</th>
						<th scope="col"><span class="sr-only">(controls)</span></th>
					</tr>
				</thead>
				<tbody>
				<xsl:for-each select="content[@class='Container']">
					<tr>
						<td scope="row"><xsl:value-of select="content[@id='timestamp']"/></td>
						<td><xsl:value-of select="content[@id='table']"/></td>
						<td>
							<xsl:if test="content[@id='data']">
								<xsl:for-each select="content[@id='data']/*">
									<xsl:value-of select="name(.)"/>
									<xsl:if test="position()!=last()">, </xsl:if>
								</xsl:for-each>
							</xsl:if>
							<xsl:if test="not(content[@id='data'])"><i class="text-muted">row deleted</i></xsl:if>
						</td>
						<td><xsl:if test="not(content[@id='previous'])"><span class="fas fa-check"></span></xsl:if></td>
						<td><xsl:value-of select="content[@id='blame']"/></td>
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
<xsl:for-each select="content[@class='Container']">
<div class="modal fade" id="modal{@id}" tabindex="-1" role="dialog" aria-labelledby="modal{@id}_label" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content border-dark">
			<div class="modal-header text-white bg-dark">
				<h3 class="modal-title" id="modal{@id}_label">Change Specifics</h3>
				<button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
					<span class="fas fa-times" aria-hidden="true"></span>
				</button>
			</div>
			<div class="modal-body">
			<xsl:if test="content[@id='data']">
				<div class="card mb-2">
					<h4 class="card-header text-white bg-secondary">
						New Data For This Change
					</h4>
					<div class="card-body p-0">
						<div class="table-responsive"><table class="table table-hover m-0">
							<thead class="thead-light">
								<tr>
									<th scope="col">Column</th>
									<th scope="col">Data</th>
								</tr>
							</thead>
							<tbody>
								<xsl:for-each select="content[@id='data']/*">
								<tr>
									<td class="h6" scope="row"><xsl:value-of select="name(.)"/></td>
									<td><xsl:value-of select="."/></td>
								</tr>
								</xsl:for-each>
							</tbody>
						</table></div>
					</div>
				</div>
			</xsl:if>
			<xsl:if test="content[@id='previous']">
				<div class="card">
					<h4 class="card-header text-white bg-secondary">
						Previous Data
					</h4>
					<div class="card-body p-0">
						<xsl:for-each select="content[@id='previous']/*">
							<div class="table-responsive mb-1"><table class="table table-hover m-0">
								<thead class="thead-light">
									<tr>
										<th scope="col">Column</th>
										<th scope="col">Data</th>
									</tr>
								</thead>
								<tbody>
									<xsl:for-each select="*">
									<tr>
										<td class="h6" scope="row"><xsl:value-of select="name(.)"/></td>
										<td><xsl:value-of select="."/></td>
									</tr>
									</xsl:for-each>
								</tbody>
							</table></div>
						</xsl:for-each>
					</div>
				</div>
			</xsl:if>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
</xsl:for-each>
</xsl:template>

</xsl:stylesheet>
