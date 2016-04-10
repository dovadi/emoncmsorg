var process_info = {
    // <li><b>PHPFiwa</b> produces a series of downsampled averaged layers which gives a more accurate representation of the data when viewing the data over a large time range. </li>

    '1':"<p><b>Log to feed:</b> This processor logs the current selected input to a timeseries feed which can then be used to explore historic data. This is recommended for logging power, temperature, humidity, voltage and current data.</p><p><b>Feed engine:</b><ul><li><b>PHPFina</b> is the recommended feed engine it is a basic fixed interval timeseries engine and is the same engine used on the EmonPi which makes it possible to migrate the data between emoncms.org and the EmonPi. A fixed interval feed engine makes use of the typically regular nature of timeseries data the timestamp does not need to be stored as data is recorded and posted at a fixed interval i.e every 10 seconds halving the required disk space and providing faster data access.</li><li><b>PHPTimeseries</b> is for data posted at a non regular interval such as on state change.</li></ul></p><p><b>Feed interval:</b> When selecting the feed interval select an interval that is the same as, or longer than the update rate that is set in your monitoring equipment. Setting the interval rate to be shorter than the update rate of the equipment causes un-needed disk space to be used up.</p>",
    
    '2':"<p><b>Scale:</b> Scale input by value given. This can be useful for calibrating a particular variable on the web rather than by reprogramming hardware. Result is passed back for further processing by the next processor in the input processing list</p>",
    
    '3':"<p><b>Offset:</b> Offset input by value given. This can again be useful for calibrating a particular variable on the web rather than by reprogramming hardware. Result is passed back for further processing by the next processor in the input processing list</p>",

    '4':"<p><b>Power to kWh:</b> Convert a power value in Watts to a cumulative kWh feed.<br><br><b>Visualisation tip:</b> Feeds created with this input processor can be used to generate daily kWh data using the BarGraph visualisation with the delta property set to 1. See forum thread here for an example <a href='https://openenergymonitor.org/emon/node/12308'>Creating kWh per day bar graphs from Accumulating kWh </a></p>",

    '5':"<p><b>Power to kWh/d:</b> Convert a power value in Watts to a feed that contains an entry for the total energy used each day. It is recommended to use the power to kWh processor above rather than this approach now as this processor only supports daily data created at UTC midnight.</p>",

    '6':"<p><b>x input:</b> This multiplies the current selected input with another input as selected from the dropdown menu. The result is passed back for further processing by the next processor in the input processing list.</p>",
    
    '12':"<p><b>/ input:</b> This divides the current selected input with another input as selected from the dropdown menu. The result is passed back for further processing by the next processor in the input processing list.</p>",
    
    '11':"<p><b>+ input:</b> This adds the selected input from the dropdown menu to the current input. The result is passed back for further processing by the next processor in the input processing list.</p>",
    
    '22':"<p><b>- input:</b> This subtracts the selected input from the dropdown menu from the current input. The result is passed back for further processing by the next processor in the input processing list.</p>",
    
    '14':"<p><b>Accumulator:</b> Output feed accumulates by input value</p>", 
    
    '34':"<b>Wh Accumulator:</b> Use with emontx, emonth or emonpi pulsecount or an emontx running firmware <i>emonTxV3_4_continuous_kwhtotals</i> sending cumulative watt hours.<br><br>This processor ensures that when the emontx is reset the watt hour count in emoncms does not reset, it also checks filter's out spikes in energy use that are larger than a max power threshold set in the processor, assuming these are error's, the max power threshold is set to 25kW. <br><br><b>Visualisation tip:</b> Feeds created with this input processor can be used to generate daily kWh data using the BarGraph visualisation with the delta property set to 1 and scale set to 0.001. See forum thread here for an example <a href='https://openenergymonitor.org/emon/node/12308'>Creating kWh per day bar graphs from Accumulating kWh </a></p>"
}


