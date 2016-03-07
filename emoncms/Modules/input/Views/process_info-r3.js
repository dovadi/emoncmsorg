var process_info = {

    '1':"<p><b>Log to feed:</b> This processor logs the current selected input to a timeseries feed which can then be used to explore historic data. This is recommended for logging power, temperature, humidity, voltage and current data.</p><p><b>Feed engine:</b><ul><li><b>PHPFina</b> is the default feed engine it is a basic fixed interval timeseries engine and is the same engine used on the EmonPi which makes it possile to migrate the data between emoncms.org and the EmonPi. PHPFina requires less processing and causes less disk load than PHPFiwa.</li><li><b>PHPFiwa</b> produces a series of downsampled averaged layers which gives a more accurate representation of the data when viewing the data over a large time range. </li><li><b>PHPTimeseries</b> is for data posted at a non regular interval such as on state change.</li></ul></p><p><b>Feed interval:</b> When selecting the feed interval select an interval that is the same as, or longer than the update rate that is set in your monitoring equipment. Setting the interval rate to be shorter than the update rate of the equipment causes un-needed disk space to be used up.</p>",
    
    '2':"Scale input by value given. This can be useful for calibrating a particular variable on the web rather than by reprogramming hardware. Result is passed back for further processing by the next processor in the input processing list",
    
    '3':"Offset input by value given. This can again be useful for calibrating a particular variable on the web rather than by reprogramming hardware. Result is passed back for further processing by the next processor in the input processing list",

    '4':"Convert a power value in Watts to a cumulative kWh timeseries.<br><br><b>Visualisation tip:</b> This kWh timeseries can be used to generate daily kWh data using the BarGraph visualisation with the delta property set to 1.",

    '5':"Convert a power value in Watts to a feed that contains an entry for the total energy used each day. It is recommended to use the power to kWh processor above rather than this approach now due to better timezone support.",

    '6':"This multiplies the current selected input with another input as selected from the dropdown menu. The result is passed back for further processing by the next processor in the input processing list.",
    
    '12':"This divides the current selected input with another input as selected from the dropdown menu. The result is passed back for further processing by the next processor in the input processing list.",
    
    '11':"This adds the selected input from the dropdown menu to the current input. The result is passed back for further processing by the next processor in the input processing list.",
    
    '22':"This subtracts the selected input from the dropdown menu from the current input. The result is passed back for further processing by the next processor in the input processing list.",
    
    '14':"Output feed accumulates by input value",  

    '15':"Output feed is the difference between the current value and the last",   
    
    '7':"Counts the amount of time that an input is high in each day and logs the result to a feed. Created for counting the number of hours a solar hot water pump is on each day",
    
    '34':"To be used in conjunction with an emontx sending total watt hours elapsed to emoncms. This processor ensures that when the emontx is reset the watt hour count in emoncms does not reset, it also checks filter's out spikes in energy use that are larger than a max power threshold set in the processor, assuming these are error's, the max power threshold is set to 25kW. <br><br><b>Visualisation tip:</b> This accumulating Wh timeseries can be used to generate daily kWh data using the BarGraph visualisation with the delta property set to 1 and scale set to 0.001.",
    
    '23':"<b>Tip:</b> You can create daily kWh data from a kWh feed on the fly using the BarGraph visualisation. Create a kWh feed directly with a log to feed process and then use the BarGraph visualisation with delta set to 1.",
    
    '21':"Convert accumulating kWh to instantaneous power"
}


