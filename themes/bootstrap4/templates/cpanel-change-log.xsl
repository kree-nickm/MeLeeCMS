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
						<xsl:choose>
							<xsl:when test="not(content[@id='data']/row)">
								<i class="text-muted">row(s) deleted</i>
							</xsl:when>
							<xsl:when test="count(content[@id='data']/row) &gt; 1">
								<i>multiple rows</i>
							</xsl:when>
							<xsl:otherwise>
							<xsl:for-each select="content[@id='data']/*[1]/*">
								<xsl:value-of select="name(.)"/>
								<xsl:if test="position()!=last()">, </xsl:if>
							</xsl:for-each>
							</xsl:otherwise>
						</xsl:choose>
						</td>
						<td><xsl:if test="count(content[@id='previous']/row) &lt; count(content[@id='data']/row)"><span class="fas fa-check"></span></xsl:if></td>
						<td><xsl:value-of select="content[@id='blame']"/></td>
						<td>
							<button type="button" class="btn btn-secondary fas fa-info" data-toggle="modal" data-target="#modal{@id}" aria-label="Details"></button>
						</td>
					</tr>
				</xsl:for-each>
				</tbody>
			</table></div>
		</div>
	</div>
</div>
</xsl:template>

</xsl:stylesheet>
