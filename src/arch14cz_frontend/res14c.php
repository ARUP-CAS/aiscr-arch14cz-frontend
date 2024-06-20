<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5"></script>
<style>
#loading-indicator {
	position: relative;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%); /* Center the element */
	text-align: center; /* Center text within the element */
	background-color: white;
	color: black;
}
#explanation-box {
	border: 1px solid black;
	background-color: white;
	color: black;
	padding: 10px; margin: 10px;
	font-size: 90%;
}
#explanation-box h3 {
	color: black;
}
.input-form form {
	float: left;
	margin-right: 10px;
}
#resultControls {
	display: none;
}
</style>

<div class="column">
<h2 class="first">Radiocarbon Dating Resolution Calculator</h2>
<p>Calculate expected temporal resolution of radiocarbon dating based on the expected actual age of the samples.</p>
<div class="input-form" style="border: 1px solid grey; padding: 10px; margin: 10px;">
	<p><span style="font-weight: bold;">Enter sample age</span> (in years AD, use negative values for BC):</p>
	<form oninput="updateLink()">
	<label for="lowerBound">Minimum:</label>
	<input id="lowerBound" type="number" value="-18000">
	<label for="upperBound">Maximum:</label>
	<input id="upperBound" type="number" value="1950">
	</form>
	<button id="plotButton">Plot</button>
	<div style="padding: 10px; margin: 10px;">
		<div id="loading-indicator" style="display: none;">Loading...</div>
		<canvas id="plotContainer" style="display: none; background-color: white;"></canvas>
	</div>
	<div id="resultControls">
		<button id="exportButton" style="display: none;">Download Data (CSV)</button>
		<p><a id="resultLink" href="#">Link to Result</a></p>
	</div>
</div>
<div id="explanation-box">
	<h3>Example Use:</h3>
	<p>Let's say we want to know the expected dating resolution for samples with an actual age around 1000 BC. Here's how we can do it:</p>
	<ol>
		<li><strong>Enter Age Range:</strong> In the "Enter sample age" section, input -1050 as the minimum value and -950 as the maximum value. These correspond to 1050 BC and 950 BC.</li>
		<li><strong>Plot the Data:</strong> Click the "Plot" button.</li>
	</ol>
	<h3>Understanding the Results:</h3>
	<ul>
		<li>The resulting graph will show the resolution of radiocarbon dating for this age range.</li>
		<li>The grey shaded area represents the 90% confidence interval, indicating the range within which we can expect most of our dates to fall.</li>
		<li>The black line represents the mean resolution.</li>
	</ul>
	<h3>Interpreting the Data:</h3>
	<p>For the given age range (1050-950 BC), the graph shows that the dating uncertainty ranges (2-sigma or 95.45% confidence interval) vary between approximately 100 and 190 years.</p>
	<p>Statistically (according to a binomial probability model), if we were to date 20 samples from this period, we can expect 18 of them to have a dating uncertainty range of 190 years or less. To have a 90% chance of obtaining at least one sample with a range of 100 years or less, we need to date at least 22 samples.</p>
	<p>In general, <strong>18 out of 20 dated samples</strong> will have a dating uncertainty range at the <strong>upper bound</strong> of the 90% confidence interval, and to have a 90% chance of obtaining at least one sample with a range at the <strong>lower bound</strong>, we would need to date <strong>at least 22 samples</strong>.</p>
</div>
<p>Project home: <a href="https://github.com/demjanp/res14c_online">https://github.com/demjanp/res14c_online</a> (Python script used to generate the data as well as the full open source code).</p>
<p>For an overview of the method see: Svetlik et al. (2019) <a href="https://doi.org/10.1017/RDC.2019.134">DOI: 10.1017/RDC.2019.134</a>.</p>
<p>The expected measurement uncertainty for 0 BP is ±15 radiocarbon years. This value grows exponentially depending on the age of the sample (see <a href="https://doi.org/10.1017/RDC.2019.134">Svetlik et al. 2019</a> for the exact formula).</p>
<p>Radiocarbon dates are calibrated using atmospheric data from the IntCal20 dataset by: Reimer et al. (2016) <a href="https://doi.org/10.2458/azu_js_rc.55.16947">DOI: 10.2458/azu_js_rc.55.16947</a></p>
<p>Ranges using different measurement uncertainty values or calibration curves can be calculated using a Python script. See the <a href="https://github.com/demjanp/res14c_online">Res14C GitHub repository</a> for details.</p>
<p>Development of the Radiocarbon Dating Resolution Calculator software was supported by project OP JAC "Ready for the future: understanding long-term resilience of the human culture (RES-HUM)", Reg. No. CZ.02.01.01/00/22_008/0004593 of the MEYS CR and EU.</p>
<p><img style="margin: 10px; padding: 10px; background-color: white;" src="static/EU_MEYS.jpg"/></p>
<script>
	function getQueryParams() {
		const params = {};
		window.location.search.substr(1).split('&').forEach(pair => {
			const [key, value] = pair.split('=');
			params[decodeURIComponent(key)] = decodeURIComponent(value);
		});
		return params;
	}
	const queryParams = getQueryParams();
	if (queryParams.lowerBound) {
		document.getElementById('lowerBound').value = queryParams.lowerBound;
	}
	if (queryParams.upperBound) {
		document.getElementById('upperBound').value = queryParams.upperBound;
	}
	
	const lowerBoundInput = document.getElementById("lowerBound");
	const upperBoundInput = document.getElementById("upperBound");
	const plotButton = document.getElementById("plotButton");
	const exportButton = document.getElementById("exportButton");
	const plotContainer = document.getElementById("plotContainer");
	const loadingIndicator = document.getElementById("loading-indicator");
	
	function showLinks() {
		document.getElementById('resultControls').style.display = 'block';
	}
	
	let chart = 0;
	let filteredData = 0
	let csvBlob; // Declare the blob variable outside the function
	
	plotButton.addEventListener("click", async () => {
		loadingIndicator.style.display = 'block';  // Show loading indicator before fetching data
		plotContainer.style.display = 'none';
		
		const lowerBound = parseFloat(lowerBoundInput.value);
		const upperBound = parseFloat(upperBoundInput.value);

		const response = await fetch("static/c14_resolution_data.csv");
		const csvData = await response.text();

		const rows = csvData.split("\n").map(row => row.split(",")); // Parse CSV data

		// Filter data and extract confidence intervals
		filteredData = rows.filter(row => {
			const x = parseFloat(row[0]);
			return x >= lowerBound && x <= upperBound;
		});

		const labels = filteredData.map(row => row[0]);
		const values = filteredData.map(row => row[1]);
		const lowerCI = filteredData.map(row => row[2]);  // Lower CI
		const upperCI = filteredData.map(row => row[3]);  // Upper CI

		// Create chart data with confidence interval areas and lines
		const chartData = {
			labels: labels,
			datasets: [{
				label: 'Mean',
				data: values,
				backgroundColor: 'rgba(255, 255, 255, 0.2)',
				borderColor: 'rgba(0, 0, 0, 1)',
				borderWidth: 2,
				pointRadius: 0
			}, {
				label: '',
				data: lowerCI,
				backgroundColor: 'rgba(255, 255, 255, 1)', // Set fill color for CI area
				borderColor: 'transparent', // Hide border for area
				fill: true,
				pointRadius: 0
			}, {
				label: '90% Confidence Interval',
				data: upperCI,
				backgroundColor: 'rgba(50, 50, 50, 0.5)', // Set fill color for CI area
				borderColor: 'transparent', // Hide border for area
				fill: true,
				pointRadius: 0
			}]
		};

		if (chart !== 0) {
			chart.destroy();
		}

		chart = new Chart(plotContainer, {
			type: 'line',
			data: chartData,

			options: {
				plugins: {
					title: {
						display: true,
						text: 'Expected Resolution of C-14 Dating'
					}
				},
				scales: {
					x: {
						title: {
							display: true,
							text: 'Sample Age (years AD, negative = BC)'
						}
					},
					y: {
						title: {
							display: true,
							text: 'Resolution (years)'
						}
					}
				}
			}
		});
		
		plotContainer.style.display = 'block';
		exportButton.style.display = 'block'; // Show exportButton only after plot is generated
		loadingIndicator.style.display = 'none'; // Hide loading indicator after data processing
		
		// Prepare CSV data string with headers
		const headerRow = 'Sample Age (yrs AD);Mean Resolution (yrs);5th Percentile Resolution (yrs);95th Percentile Resolution (yrs);Uncertainty Used (C-14 yrs)\n';
		const csvContent = filteredData.map(row => row.join(";")).join("\n");
		const combinedData = headerRow + csvContent;
		csvBlob = new Blob([combinedData], { type: 'text/csv;charset=utf-8' });
		
		showLinks();
	});

	exportButton.addEventListener('click', () => {
		const fileName = 'c14_resolution_export.csv';
		saveAs(csvBlob, fileName);  // Use FileSaver.js to save the blob
	});
	
	if (queryParams.lowerBound && queryParams.upperBound) {
		plotButton.click();
	}
	
	function updateLink() {
		var lowerBound = document.getElementById('lowerBound').value;
		var upperBound = document.getElementById('upperBound').value;
		var link = `https://arch14.aiscr.cz/?page=res14c&lowerBound=${lowerBound}&upperBound=${upperBound}`;
		document.getElementById('resultLink').href = link;
		document.getElementById('resultLink').innerText = "Link to result";
	}
	updateLink();
</script>
</div>


